@extends('layouts.viewer')

@section('content')
    @livewire('workspace-viewer', ['workspace' => $workspace])
@endsection
