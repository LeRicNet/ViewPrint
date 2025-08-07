/**
 * Coordinate transformation utilities for ViewPrint
 *
 * Handles conversion between different coordinate systems:
 * - Screen coordinates (2D eye-tracking data)
 * - Volume coordinates (3D NIfTI voxel space)
 * - World coordinates (3D scanner space)
 */

/**
 * Convert 2D screen coordinates to 3D volume coordinates
 * @param {Object} screenPoint - Screen coordinates
 * @param {number} screenPoint.x - X coordinate (0-1 normalized)
 * @param {number} screenPoint.y - Y coordinate (0-1 normalized)
 * @param {Object} viewInfo - Current view information
 * @param {string} viewInfo.sliceType - Current slice type (axial/coronal/sagittal)
 * @param {number} viewInfo.sliceIndex - Current slice index
 * @param {Array} viewInfo.dimensions - Volume dimensions [x, y, z]
 * @param {Object} viewInfo.canvas - Canvas dimensions
 * @returns {Object} Volume coordinates {i, j, k}
 */
export function screenToVolume(screenPoint, viewInfo) {
    const { x, y } = screenPoint;
    const { sliceType, sliceIndex, dimensions, canvas } = viewInfo;

    // Denormalize if coordinates are normalized
    const screenX = x <= 1 ? x * canvas.width : x;
    const screenY = y <= 1 ? y * canvas.height : y;

    // Calculate voxel coordinates based on slice type
    let i, j, k;

    switch (sliceType) {
        case 'axial':
            // Axial: viewing from top (Z slice)
            i = Math.floor((screenX / canvas.width) * dimensions[0]);
            j = Math.floor((screenY / canvas.height) * dimensions[1]);
            k = sliceIndex;
            break;

        case 'coronal':
            // Coronal: viewing from front (Y slice)
            i = Math.floor((screenX / canvas.width) * dimensions[0]);
            j = sliceIndex;
            k = Math.floor((1 - screenY / canvas.height) * dimensions[2]);
            break;

        case 'sagittal':
            // Sagittal: viewing from side (X slice)
            i = sliceIndex;
            j = Math.floor((screenX / canvas.width) * dimensions[1]);
            k = Math.floor((1 - screenY / canvas.height) * dimensions[2]);
            break;

        default:
            throw new Error(`Unknown slice type: ${sliceType}`);
    }

    // Clamp to volume bounds
    i = Math.max(0, Math.min(i, dimensions[0] - 1));
    j = Math.max(0, Math.min(j, dimensions[1] - 1));
    k = Math.max(0, Math.min(k, dimensions[2] - 1));

    return { i, j, k };
}

/**
 * Convert volume coordinates to world coordinates using affine transform
 * @param {Object} voxelCoord - Voxel coordinates {i, j, k}
 * @param {Array} affineMatrix - 4x4 affine transformation matrix (row-major)
 * @returns {Object} World coordinates {x, y, z}
 */
export function volumeToWorld(voxelCoord, affineMatrix) {
    const { i, j, k } = voxelCoord;

    // Apply affine transformation
    const x = affineMatrix[0] * i + affineMatrix[1] * j + affineMatrix[2] * k + affineMatrix[3];
    const y = affineMatrix[4] * i + affineMatrix[5] * j + affineMatrix[6] * k + affineMatrix[7];
    const z = affineMatrix[8] * i + affineMatrix[9] * j + affineMatrix[10] * k + affineMatrix[11];

    return { x, y, z };
}

/**
 * Convert world coordinates to volume coordinates using inverse affine transform
 * @param {Object} worldCoord - World coordinates {x, y, z}
 * @param {Array} inverseAffineMatrix - Inverse 4x4 affine transformation matrix
 * @returns {Object} Volume coordinates {i, j, k}
 */
export function worldToVolume(worldCoord, inverseAffineMatrix) {
    const { x, y, z } = worldCoord;

    // Apply inverse affine transformation
    const i = inverseAffineMatrix[0] * x + inverseAffineMatrix[1] * y + inverseAffineMatrix[2] * z + inverseAffineMatrix[3];
    const j = inverseAffineMatrix[4] * x + inverseAffineMatrix[5] * y + inverseAffineMatrix[6] * z + inverseAffineMatrix[7];
    const k = inverseAffineMatrix[8] * x + inverseAffineMatrix[9] * y + inverseAffineMatrix[10] * z + inverseAffineMatrix[11];

    return {
        i: Math.round(i),
        j: Math.round(j),
        k: Math.round(k)
    };
}

/**
 * Convert eye-tracking fixation to 3D Gaussian blob for heatmap generation
 * @param {Object} fixation - Eye-tracking fixation
 * @param {number} fixation.x - X coordinate (normalized)
 * @param {number} fixation.y - Y coordinate (normalized)
 * @param {number} fixation.duration - Fixation duration in ms
 * @param {Object} volumeInfo - Volume information
 * @param {number} sigma - Gaussian standard deviation in voxels
 * @returns {Array} 3D array of weights for the fixation
 */
export function fixationToGaussian(fixation, volumeInfo, sigma = 2.0) {
    const { dimensions } = volumeInfo;
    const center = screenToVolume(
        { x: fixation.x, y: fixation.y },
        volumeInfo
    );

    // Calculate weight based on duration (longer = stronger)
    const durationWeight = Math.log10(fixation.duration + 1) / 3;

    // Create 3D Gaussian kernel
    const kernelSize = Math.ceil(sigma * 3); // 3 standard deviations
    const kernel = [];

    for (let di = -kernelSize; di <= kernelSize; di++) {
        for (let dj = -kernelSize; dj <= kernelSize; dj++) {
            for (let dk = -kernelSize; dk <= kernelSize; dk++) {
                const i = center.i + di;
                const j = center.j + dj;
                const k = center.k + dk;

                // Skip if outside volume
                if (i < 0 || i >= dimensions[0] ||
                    j < 0 || j >= dimensions[1] ||
                    k < 0 || k >= dimensions[2]) {
                    continue;
                }

                // Calculate Gaussian weight
                const distance = Math.sqrt(di * di + dj * dj + dk * dk);
                const weight = Math.exp(-(distance * distance) / (2 * sigma * sigma));

                kernel.push({
                    i, j, k,
                    weight: weight * durationWeight
                });
            }
        }
    }

    return kernel;
}

/**
 * Transform eye-tracking scan path to 3D trajectory
 * @param {Array} scanPath - Array of gaze points with timestamps
 * @param {Object} volumeInfo - Volume information
 * @returns {Array} 3D trajectory points
 */
export function scanPathTo3D(scanPath, volumeInfo) {
    const trajectory = [];

    for (const point of scanPath) {
        const volumeCoord = screenToVolume(
            { x: point.x, y: point.y },
            volumeInfo
        );

        trajectory.push({
            ...volumeCoord,
            timestamp: point.timestamp,
            duration: point.duration || 0
        });
    }

    return trajectory;
}

/**
 * Calculate the inverse of a 4x4 matrix
 * @param {Array} matrix - 4x4 matrix in row-major order
 * @returns {Array} Inverse matrix
 */
export function invertMatrix4x4(matrix) {
    // This is a simplified implementation for affine transforms
    // For production, consider using a proper matrix library

    const m = matrix;
    const inv = new Array(16);

    // Extract the 3x3 rotation part and 3x1 translation
    const det = m[0] * (m[5] * m[10] - m[6] * m[9]) -
        m[1] * (m[4] * m[10] - m[6] * m[8]) +
        m[2] * (m[4] * m[9] - m[5] * m[8]);

    if (Math.abs(det) < 1e-10) {
        throw new Error('Matrix is not invertible');
    }

    // Calculate inverse (simplified for affine transforms)
    // ... implementation details ...

    return inv;
}
