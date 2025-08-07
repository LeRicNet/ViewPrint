/**
 * File validation utilities for ViewPrint
 *
 * Client-side validation for NIfTI files before upload
 * Provides quick checks to improve user experience
 */

/**
 * NIfTI file magic numbers
 */
const NIFTI_MAGIC = {
    NII: [0x6E, 0x2B, 0x31, 0x00], // 'n+1\0' - .nii single file
    PAIR: [0x6E, 0x69, 0x31, 0x00], // 'ni1\0' - .hdr/.img pair
};

/**
 * Validate a file is a NIfTI format
 * @param {File} file - File object to validate
 * @returns {Promise<Object>} Validation result
 */
export async function validateNiftiFile(file) {
    const result = {
        valid: false,
        errors: [],
        warnings: [],
        metadata: {}
    };

    try {
        // Check file extension
        const extension = file.name.toLowerCase().split('.').pop();
        const validExtensions = ['nii', 'gz', 'hdr', 'img'];

        if (!validExtensions.includes(extension)) {
            result.errors.push(`Invalid file extension: .${extension}`);
            return result;
        }

        // Check if it's a gzipped file
        const isGzipped = file.name.toLowerCase().endsWith('.gz');
        result.metadata.compressed = isGzipped;

        // Check file size
        const maxSize = 512 * 1024 * 1024; // 512MB
        if (file.size > maxSize) {
            result.errors.push(`File too large: ${formatFileSize(file.size)} (max: ${formatFileSize(maxSize)})`);
            return result;
        }

        result.metadata.fileSize = file.size;

        // For detailed validation, read file header
        if (!isGzipped) {
            const headerData = await readFileHeader(file, 352); // NIfTI-1 header size
            const headerValidation = validateNiftiHeader(headerData);

            if (!headerValidation.valid) {
                result.errors.push(...headerValidation.errors);
                return result;
            }

            result.metadata = { ...result.metadata, ...headerValidation.metadata };
        } else {
            // For gzipped files, we can't easily validate without decompressing
            result.warnings.push('Compressed file - full validation will occur server-side');
        }

        result.valid = result.errors.length === 0;

    } catch (error) {
        result.errors.push(`Validation error: ${error.message}`);
    }

    return result;
}

/**
 * Read the header portion of a file
 * @param {File} file - File to read
 * @param {number} bytes - Number of bytes to read
 * @returns {Promise<ArrayBuffer>} File header data
 */
async function readFileHeader(file, bytes) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        const blob = file.slice(0, bytes);

        reader.onload = (e) => resolve(e.target.result);
        reader.onerror = (e) => reject(new Error('Failed to read file'));

        reader.readAsArrayBuffer(blob);
    });
}

/**
 * Validate NIfTI header data
 * @param {ArrayBuffer} headerBuffer - Header data
 * @returns {Object} Validation result
 */
function validateNiftiHeader(headerBuffer) {
    const result = {
        valid: false,
        errors: [],
        metadata: {}
    };

    try {
        const view = new DataView(headerBuffer);

        // Check magic string at offset 344
        const magic = [];
        for (let i = 0; i < 4; i++) {
            magic.push(view.getUint8(344 + i));
        }

        const isValidMagic =
            magic.every((byte, i) => byte === NIFTI_MAGIC.NII[i]) ||
            magic.every((byte, i) => byte === NIFTI_MAGIC.PAIR[i]);

        if (!isValidMagic) {
            result.errors.push('Invalid NIfTI magic string');
            return result;
        }

        // Read basic header information
        const dim = [];
        for (let i = 0; i < 8; i++) {
            dim.push(view.getInt16(40 + i * 2, true)); // little-endian
        }

        // Validate dimensions
        if (dim[0] < 3 || dim[0] > 7) {
            result.errors.push(`Invalid number of dimensions: ${dim[0]}`);
            return result;
        }

        // Extract metadata
        result.metadata.dimensions = dim.slice(1, dim[0] + 1);
        result.metadata.dataType = view.getInt16(70, true);
        result.metadata.bitDepth = view.getInt16(72, true);

        // Read voxel dimensions
        const pixdim = [];
        for (let i = 0; i < 8; i++) {
            pixdim.push(view.getFloat32(76 + i * 4, true));
        }
        result.metadata.voxelSize = pixdim.slice(1, 4);

        // Estimate uncompressed size
        const voxelCount = result.metadata.dimensions.reduce((a, b) => a * b, 1);
        const bytesPerVoxel = result.metadata.bitDepth / 8;
        result.metadata.estimatedSize = voxelCount * bytesPerVoxel;

        result.valid = true;

    } catch (error) {
        result.errors.push(`Header parsing error: ${error.message}`);
    }

    return result;
}

/**
 * Validate multiple files for batch upload
 * @param {FileList|Array<File>} files - Files to validate
 * @returns {Promise<Array>} Validation results for each file
 */
export async function validateMultipleFiles(files) {
    const results = [];

    for (const file of files) {
        const validation = await validateNiftiFile(file);
        results.push({
            file: file,
            ...validation
        });
    }

    return results;
}

/**
 * Check if eye-tracking data file is valid
 * @param {File} file - CSV or JSON file with eye-tracking data
 * @returns {Promise<Object>} Validation result
 */
export async function validateEyeTrackingFile(file) {
    const result = {
        valid: false,
        errors: [],
        warnings: [],
        metadata: {}
    };

    try {
        const extension = file.name.toLowerCase().split('.').pop();

        if (!['csv', 'json', 'tsv'].includes(extension)) {
            result.errors.push(`Invalid file type: .${extension}. Expected .csv, .json, or .tsv`);
            return result;
        }

        // Check file size (eye-tracking files should be relatively small)
        const maxSize = 50 * 1024 * 1024; // 50MB
        if (file.size > maxSize) {
            result.errors.push(`File too large for eye-tracking data: ${formatFileSize(file.size)}`);
            return result;
        }

        // Sample the file to check format
        const sample = await readFileHeader(file, 1024); // Read first 1KB
        const text = new TextDecoder().decode(sample);

        if (extension === 'json') {
            try {
                // Try to parse as JSON
                const lines = text.split('\n').filter(l => l.trim());
                JSON.parse(lines[0]); // Test first line
                result.metadata.format = 'json';
            } catch {
                result.errors.push('Invalid JSON format');
                return result;
            }
        } else {
            // Check CSV/TSV format
            const delimiter = extension === 'tsv' ? '\t' : ',';
            const lines = text.split('\n').filter(l => l.trim());

            if (lines.length < 2) {
                result.errors.push('File appears to be empty');
                return result;
            }

            // Check header
            const header = lines[0].split(delimiter);
            const requiredColumns = ['timestamp', 'x', 'y'];
            const hasRequired = requiredColumns.every(col =>
                header.some(h => h.toLowerCase().includes(col))
            );

            if (!hasRequired) {
                result.warnings.push(`Expected columns: ${requiredColumns.join(', ')}`);
            }

            result.metadata.format = extension;
            result.metadata.columns = header;
        }

        result.valid = result.errors.length === 0;

    } catch (error) {
        result.errors.push(`Validation error: ${error.message}`);
    }

    return result;
}

/**
 * Format file size for display
 * @param {number} bytes - File size in bytes
 * @returns {string} Formatted file size
 */
export function formatFileSize(bytes) {
    const units = ['B', 'KB', 'MB', 'GB'];
    let size = bytes;
    let unitIndex = 0;

    while (size >= 1024 && unitIndex < units.length - 1) {
        size /= 1024;
        unitIndex++;
    }

    return `${size.toFixed(1)} ${units[unitIndex]}`;
}

/**
 * Estimate memory usage for a NIfTI file
 * @param {Object} metadata - File metadata from validation
 * @returns {Object} Memory usage estimate
 */
export function estimateMemoryUsage(metadata) {
    const { dimensions, bitDepth } = metadata;

    if (!dimensions || !bitDepth) {
        return { error: 'Missing metadata' };
    }

    const voxelCount = dimensions.reduce((a, b) => a * b, 1);
    const bytesPerVoxel = bitDepth / 8;

    // Estimate GPU memory (texture storage + overhead)
    const textureMemory = voxelCount * bytesPerVoxel;
    const overheadFactor = 1.5; // Account for mipmaps, buffers, etc.
    const totalMemory = textureMemory * overheadFactor;

    return {
        voxelCount,
        textureMemory,
        estimatedTotal: totalMemory,
        formatted: formatFileSize(totalMemory)
    };
}
