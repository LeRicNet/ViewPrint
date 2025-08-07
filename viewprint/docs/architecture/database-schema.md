# ViewPrint Database Schema Documentation

## Overview
The database schema supports flexible workspace-based analysis of eye-tracking data on NIfTI volumes. The design prioritizes flexibility and extensibility while maintaining referential integrity.

## Core Tables

### users
Standard Laravel authentication table with additional fields for research context.

### volumes
Stores NIfTI volume metadata and file references.

```sql
volumes
├── id (bigint, primary key)
├── name (varchar) - User-friendly name
├── description (text, nullable) - Detailed description
├── file_path (varchar) - Path to NIfTI file in storage
├── file_size (bigint) - Size in bytes
├── dimensions (json) - [x, y, z] voxel dimensions
├── voxel_size (json) - [x, y, z] voxel size in mm
├── metadata (json) - Additional NIfTI header data
│   ├── coordinate_system - RAS, LAS, etc.
│   ├── datatype - uint8, float32, etc.
│   └── ... other header fields
├── uploaded_by (bigint, foreign key → users)
├── created_at (timestamp)
└── updated_at (timestamp)
```

### participants
Research participants whose eye-tracking data is being analyzed.

```sql
participants
├── id (bigint, primary key)
├── code (varchar, unique) - Anonymous participant code
├── group (varchar, nullable) - e.g., "expert", "novice"
├── metadata (json) - Flexible participant attributes
│   ├── experience_years
│   ├── specialty
│   └── ... custom fields
├── created_at (timestamp)
└── updated_at (timestamp)
```

### eye_tracking_sessions
Records of participants viewing specific volumes.

```sql
eye_tracking_sessions
├── id (bigint, primary key)
├── participant_id (bigint, foreign key → participants)
├── volume_id (bigint, foreign key → volumes)
├── duration_ms (integer) - Total viewing time
├── raw_data_path (varchar) - Path to CSV/raw data
├── processed_data_path (varchar, nullable) - Path to processed NIfTI
├── metadata (json)
│   ├── eye_tracker_model
│   ├── sampling_rate_hz
│   ├── calibration_error
│   └── ... session-specific data
├── recorded_at (timestamp)
├── created_at (timestamp)
└── updated_at (timestamp)

Indexes:
- idx_participant_volume (participant_id, volume_id)
```

### workspaces
Analysis sessions that combine volumes and eye-tracking data.

```sql
workspaces
├── id (bigint, primary key)
├── name (varchar) - User-defined workspace name
├── description (text, nullable)
├── created_by (bigint, foreign key → users)
├── settings (json) - Workspace preferences
│   ├── default_colormap
│   ├── crosshair_visible
│   ├── auto_save_interval_seconds
│   └── keyboard_shortcuts
├── last_accessed_at (timestamp)
├── created_at (timestamp)
└── updated_at (timestamp)

Indexes:
- idx_created_by_last_accessed (created_by, last_accessed_at)
```

### workspace_layers
Individual visualization layers within a workspace.

```sql
workspace_layers
├── id (bigint, primary key)
├── workspace_id (bigint, foreign key → workspaces)
├── layer_type (enum) - base_volume, participant_volume, calculated, external
├── name (varchar) - Display name
├── position (integer) - Rendering order (0 = bottom)
├── visible (boolean, default true)
├── opacity (integer, default 75) - 0-100
├── configuration (json) - Layer-specific settings
│   For base_volume:
│   ├── volume_id
│   ├── colormap
│   └── threshold: {min, max}
│   
│   For participant_volume:
│   ├── session_ids: [] - Multiple sessions can be combined
│   ├── visualization_type - heatmap, scanpath, fixations
│   ├── colormap
│   ├── gaussian_sigma - For heatmap smoothing
│   └── time_range: {start_ms, end_ms}
│   
│   For calculated:
│   ├── calculation_type - average, difference, statistics
│   ├── source_layer_ids: []
│   ├── parameters - Calculation-specific params
│   └── cached_result_path - Path to generated NIfTI
├── created_at (timestamp)
└── updated_at (timestamp)

Indexes:
- idx_workspace_position (workspace_id, position)
- idx_workspace_visible (workspace_id, visible)
```

### calculation_jobs
Tracks background calculations for derived layers.

```sql
calculation_jobs
├── id (bigint, primary key)
├── workspace_layer_id (bigint, foreign key → workspace_layers)
├── status (enum) - pending, processing, completed, failed
├── progress (integer, default 0) - 0-100
├── started_at (timestamp, nullable)
├── completed_at (timestamp, nullable)
├── error_message (text, nullable)
├── created_at (timestamp)
└── updated_at (timestamp)
```

## JSON Schema Examples

### Volume Metadata
```json
{
  "coordinate_system": "RAS",
  "datatype": "float32",
  "units": "mm",
  "tr": 2.0,
  "phase_encoding_direction": "j-",
  "manufacturer": "Siemens"
}
```

### Layer Configuration - Participant Volume
```json
{
  "session_ids": [123, 124, 125],
  "visualization_type": "heatmap",
  "colormap": "jet",
  "gaussian_sigma": 2.0,
  "threshold": {
    "min": 0.1,
    "max": 0.95
  },
  "time_range": {
    "start_ms": 0,
    "end_ms": 30000
  }
}
```

### Layer Configuration - Calculated
```json
{
  "calculation_type": "group_difference",
  "source_layer_ids": [10, 11],
  "parameters": {
    "method": "subtract",
    "normalize": true,
    "threshold_p_value": 0.05
  },
  "cached_result_path": "storage/calculations/workspace_5_layer_12.nii.gz"
}
```

## Migration Order
1. Create users table (Laravel default)
2. Create volumes table
3. Create participants table
4. Create eye_tracking_sessions table
5. Create workspaces table
6. Create workspace_layers table
7. Create calculation_jobs table

## Performance Considerations
- Index frequently queried combinations
- Use JSON columns for flexibility while maintaining queryability
- Cache calculated NIfTI files to avoid recomputation
- Implement soft deletes for workspaces to allow recovery
