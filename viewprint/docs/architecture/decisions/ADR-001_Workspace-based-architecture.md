# ADR-001: Workspace-Based Architecture

## Status
Accepted

## Context
Traditional eye-tracking analysis tools typically bind participants to specific volumes in a rigid structure. Researchers studying viewing patterns need flexibility to:
- Compare how different participants view the same volume
- Compare how the same participant views different volumes
- Create group comparisons (experts vs novices)
- Perform statistical analyses across arbitrary groupings

The system needs to support any NIfTI volumetric data (MRI, CT, PET, fMRI) and allow researchers to freely combine data for analysis.

## Decision
We will implement a **workspace-based architecture** where:

1. **Workspaces** are flexible analysis sessions that can contain any combination of volumes and participants
2. **Layers** represent visualizations that can be freely added/removed:
    - Base volume layers (the NIfTI data being viewed)
    - Participant layers (eye-tracking overlays)
    - Calculated layers (statistics, differences, averages)
3. **No fixed relationships** between participants and volumes - any participant data can be overlaid on any spatially-compatible volume
4. **State management** happens at the workspace level, allowing save/restore of complex analysis setups

## Consequences

### Positive
- Maximum flexibility for researchers to explore data
- Supports both within-subject and between-subject comparisons
- Enables novel analysis workflows not possible with rigid structures
- Workspaces can be saved and shared for reproducible research
- New analysis types can be added as new layer types

### Negative
- More complex state management required
- Need robust validation for spatial compatibility
- UI must clearly communicate which data is being combined
- Performance considerations with many active layers

### Mitigations
- Implement clear spatial validation with helpful error messages
- Limit active layers based on performance testing
- Provide visual indicators showing data relationships
- Cache calculated layers aggressively using Redis

## Implementation Notes

### Database Schema
```sql
workspaces
  - id
  - name
  - description
  - created_by
  - settings (JSON)

workspace_layers  
  - id
  - workspace_id
  - layer_type (enum: base_volume, participant_volume, calculated)
  - name
  - position (rendering order)
  - visible
  - opacity
  - configuration (JSON - stores layer-specific data)
```

### Key Workflows
1. Create workspace → Add base volume → Add participant overlays → Toggle/adjust → Save
2. Load workspace → Modify layers → Create calculated layer → Export results
3. Compare workspaces side-by-side in separate browser tabs

## References
- [Niivue multi-layer documentation](https://github.com/niivue/niivue)
- Similar approach: Adobe Photoshop's layer system
- Inspiration: VS Code's workspace concept
