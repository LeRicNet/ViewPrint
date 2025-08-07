# ViewPrint Model Relationships Diagram

```
┌─────────────┐
│    User     │
└─────────────┘
      │ 1
      │ uploads
      ▼ *
┌─────────────┐         ┌────────────────────┐
│   Volume    │ 1     * │ EyeTrackingSession │
│             ├─────────┤                    │
│ - name      │ viewed  │ - duration_ms      │
│ - file_path │   in    │ - raw_data_path    │
│ - dimensions│         │ - processed_path   │
└─────────────┘         └────────────────────┘
                                │ *
                                │ records
                                ▼ 1
                        ┌─────────────────┐
                        │   Participant   │
                        │                 │
                        │ - code          │
                        │ - group         │
                        │ - metadata      │
                        └─────────────────┘

┌─────────────┐
│    User     │
└─────────────┘
      │ 1
      │ creates
      ▼ *
┌─────────────┐         ┌─────────────────┐
│  Workspace  │ 1     * │ WorkspaceLayer  │
│             ├─────────┤                 │
│ - name      │ contains│ - layer_type    │
│ - settings  │         │ - position      │
│             │         │ - configuration │
└─────────────┘         └─────────────────┘
                                │ 1
                                │ may have
                                ▼ 0..1
                        ┌─────────────────┐
                        │ CalculationJob  │
                        │                 │
                        │ - status        │
                        │ - progress      │
                        │ - error_message │
                        └─────────────────┘

## Layer Types and Their Configurations

### base_volume
{
  "volume_id": 123,
  "colormap": "gray",
  "threshold": {"min": 0, "max": 1}
}

### participant_volume  
{
  "session_ids": [1, 2, 3],
  "visualization_type": "heatmap",
  "colormap": "jet",
  "gaussian_sigma": 2.0,
  "time_range": {"start_ms": 0, "end_ms": 30000}
}

### calculated
{
  "calculation_type": "group_difference",
  "source_layer_ids": [10, 11],
  "parameters": {
    "method": "subtract",
    "normalize": true
  },
  "cached_result_path": "path/to/result.nii"
}

## Key Relationships

1. **User → Volume**: One-to-Many
   - A user can upload multiple volumes
   - Each volume has one uploader

2. **User → Workspace**: One-to-Many
   - A user can create multiple workspaces
   - Each workspace has one creator

3. **Volume → EyeTrackingSession**: One-to-Many
   - A volume can be viewed in multiple sessions
   - Each session views one volume

4. **Participant → EyeTrackingSession**: One-to-Many
   - A participant can have multiple viewing sessions
   - Each session has one participant

5. **Workspace → WorkspaceLayer**: One-to-Many
   - A workspace contains multiple layers
   - Each layer belongs to one workspace
   - Layers are ordered by position

6. **WorkspaceLayer → CalculationJob**: One-to-One
   - Calculated layers may have a job tracking progress
   - Each job belongs to one layer

## Important Notes

- **Cascade Delete**: When a workspace is deleted, all its layers are deleted
- **File Cleanup**: When volumes/sessions are deleted, their files are removed
- **JSON Columns**: metadata, settings, and configuration use JSON for flexibility
- **Soft Relationships**: Layers reference volumes/sessions by ID in their configuration
```
