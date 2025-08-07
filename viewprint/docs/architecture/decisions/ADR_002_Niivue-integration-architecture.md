# ADR-0012: Niivue Integration Architecture

**Date**: 2025-08-07  
**Status**: Proposed  
**Authors**: EP

## Context

ViewPrint requires a web-based volumetric image viewer capable of:
- Rendering NIfTI format files (.nii, .nii.gz)
- Supporting multiple overlaid volumes with adjustable opacity
- Providing smooth interaction (pan, zoom, slice navigation)
- Working within a Laravel Livewire application
- Maintaining high performance with medical imaging data

After evaluating options (Papaya, BrainBrowser, ITK-VTK Viewer, Niivue), we need to decide on an integration architecture for Niivue.

## Decision

We will integrate Niivue using a **hybrid Livewire-JavaScript architecture** with the following design:

### 1. Component Structure
```
┌─────────────────────────────────────────────────┐
│            WorkspaceViewer (Livewire)           │
│  - Manages workspace state                      │
│  - Handles file uploads                         │
│  - Persists to database                        │
└────────────────┬───────────────┬────────────────┘
                 │               │
     ┌───────────▼──────┐ ┌──────▼──────────┐
     │  LayerPanel      │ │ CommandPalette  │
     │  (Livewire)      │ │ (Livewire)      │
     └───────────┬──────┘ └──────┬──────────┘
                 │               │
     ┌───────────▼───────────────▼────────────┐
     │     ViewerCanvas (Alpine.js)           │
     │  - Thin wrapper around Niivue          │
     │  - Handles keyboard events             │
     │  - Bridges Livewire ↔ Niivue          │
     └────────────────┬───────────────────────┘
                      │
     ┌────────────────▼───────────────────────┐
     │   WorkspaceNiivueManager (Vanilla JS)  │
     │  - Manages Niivue instance             │
     │  - Handles all Niivue API calls        │
     │  - Emits events to Alpine/Livewire     │
     └────────────────────────────────────────┘
```

### 2. State Management Strategy

**Livewire (Server State)**
- Workspace configuration
- Layer metadata (name, type, visibility)
- File references and paths
- User permissions

**JavaScript (Client State)**
- Niivue instance
- Active volume objects
- Camera position
- Rendering settings
- Temporary UI state

**State Synchronization**
- One-way data flow: Livewire → JavaScript for layer changes
- Events flow: JavaScript → Livewire for user interactions
- Debounced sync for camera/view state

### 3. File Handling Architecture

```
User Upload → Livewire Component → Laravel Storage → Signed URL → Niivue
```

1. Files uploaded through Livewire file upload
2. Stored in `storage/app/nifti/{workspace_id}/`
3. Validated using server-side NIfTI headers check
4. Served to Niivue via signed temporary URLs
5. Cached in browser for session duration

### 4. Event System

**Livewire → JavaScript Events**
```javascript
// Via Alpine.js $wire integration
Livewire.on('layer:added', (layer) => {
    window.niivueManager.addLayer(layer);
});

Livewire.on('layer:removed', (layerId) => {
    window.niivueManager.removeLayer(layerId);
});

Livewire.on('layer:updated', (layer) => {
    window.niivueManager.updateLayer(layer);
});
```

**JavaScript → Livewire Events**
```javascript
// Via Alpine.js x-data
niivueManager.on('ready', () => {
    $wire.niivueReady();
});

niivueManager.on('error', (error) => {
    $wire.handleNiivueError(error);
});

// Debounced view state updates
niivueManager.on('viewChanged', debounce(() => {
    $wire.updateViewState(niivueManager.getViewState());
}, 1000));
```

### 5. Initial Implementation Scope

**Phase 1 Goals** (Current):
- Single NIfTI file loading
- Basic view controls (slice scrolling, zoom, pan)
- Save/restore view state
- Error handling for invalid files

**Deferred Features**:
- Multiple layers
- Eye-tracking overlays
- Calculated layers
- Real-time collaboration

## Consequences

### Positive
- **Separation of Concerns**: Clear boundaries between server and client responsibilities
- **Performance**: Niivue runs entirely client-side, no server round-trips for interactions
- **Maintainability**: Vanilla JS manager can be tested independently
- **Progressive Enhancement**: Can function with JavaScript disabled (degraded)
- **Laravel Integration**: Leverages Livewire's file upload and session handling

### Negative
- **Complexity**: Multiple layers of abstraction between Livewire and Niivue
- **State Sync**: Potential for state drift between server and client
- **Learning Curve**: Developers need to understand both Livewire and Niivue APIs
- **Memory Management**: Client responsible for cleanup of large volumes

### Mitigation Strategies

1. **Comprehensive Documentation**: Document all events and state flow
2. **TypeScript Definitions**: Add types for Niivue integration (future)
3. **Error Boundaries**: Graceful degradation if Niivue fails
4. **Memory Monitoring**: Add client-side memory usage warnings
5. **E2E Tests**: Test full stack including Niivue interactions

## Alternatives Considered

### Alternative 1: Pure Livewire with Server-Side Rendering
- Render images server-side and stream to client
- Rejected: Poor performance, high server load

### Alternative 2: SPA with Separate API
- Vue/React SPA with Laravel API backend
- Rejected: Loses Livewire benefits, increases complexity

### Alternative 3: Direct Niivue Integration
- Vanilla JavaScript without abstraction layer
- Rejected: Difficult to maintain state sync, no structure

## Implementation Notes

### Security Considerations
- NIfTI files must be validated server-side before serving
- Use signed URLs with expiration for file access
- Implement file size limits (suggest 500MB initial limit)
- Sanitize metadata before display

### Performance Guidelines
- Lazy load Niivue library only when viewer is accessed
- Implement virtual scrolling for layer lists >50 items
- Use WebWorkers for intensive calculations (future)
- Monitor and limit maximum concurrent volumes

### Browser Support
- Primary: Chrome 90+ (development focus)
- Secondary: Firefox 88+, Safari 14+, Edge 90+
- No support: Internet Explorer

## References

- [Niivue Documentation](https://github.com/niivue/niivue)
- [Livewire File Uploads](https://livewire.laravel.com/docs/file-uploads)
- [Alpine.js Integration](https://alpinejs.dev/advanced/extending)
- [Laravel Storage](https://laravel.com/docs/10.x/filesystem)

## Review History

- 2025-08-07: Initial draft
- Pending: Architecture review
- Pending: Security review
