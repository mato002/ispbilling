@extends('layouts.admin')

@section('title', 'Create Bandwidth Profile')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Create Bandwidth Profile</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('isp.bandwidth-profiles.store') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Profile Name</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name') }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="download_limit" class="form-label">Download Limit (Mbps)</label>
                                    <input type="number" class="form-control @error('download_limit') is-invalid @enderror" 
                                           id="download_limit" name="download_limit" value="{{ old('download_limit') }}" 
                                           min="1" step="0.1" required>
                                    @error('download_limit')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="upload_limit" class="form-label">Upload Limit (Mbps)</label>
                                    <input type="number" class="form-control @error('upload_limit') is-invalid @enderror" 
                                           id="upload_limit" name="upload_limit" value="{{ old('upload_limit') }}" 
                                           min="1" step="0.1" required>
                                    @error('upload_limit')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="queue_type" class="form-label">Queue Type</label>
                                    <select class="form-select @error('queue_type') is-invalid @enderror" 
                                            id="queue_type" name="queue_type" required>
                                        <option value="queue-simple" {{ old('queue_type') == 'queue-simple' ? 'selected' : '' }}>
                                            Queue Simple
                                        </option>
                                        <option value="queue-tree" {{ old('queue_type') == 'queue-tree' ? 'selected' : '' }}>
                                            Queue Tree
                                        </option>
                                    </select>
                                    @error('queue_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="parent_profile_id" class="form-label">Parent Profile</label>
                                    <select class="form-select @error('parent_profile_id') is-invalid @enderror" 
                                            id="parent_profile_id" name="parent_profile_id">
                                        <option value="">None</option>
                                        @foreach($parentProfiles as $profile)
                                            <option value="{{ $profile->id }}" 
                                                {{ old('parent_profile_id') == $profile->id ? 'selected' : '' }}>
                                                {{ $profile->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('parent_profile_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <h6 class="text-muted mb-3">Burst Settings (Optional)</h6>
                                
                                <div class="mb-3">
                                    <label for="download_burst_limit" class="form-label">Download Burst Limit (Mbps)</label>
                                    <input type="number" class="form-control @error('download_burst_limit') is-invalid @enderror" 
                                           id="download_burst_limit" name="download_burst_limit" value="{{ old('download_burst_limit') }}" 
                                           min="1" step="0.1">
                                    @error('download_burst_limit')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="upload_burst_limit" class="form-label">Upload Burst Limit (Mbps)</label>
                                    <input type="number" class="form-control @error('upload_burst_limit') is-invalid @enderror" 
                                           id="upload_burst_limit" name="upload_burst_limit" value="{{ old('upload_burst_limit') }}" 
                                           min="1" step="0.1">
                                    @error('upload_burst_limit')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="download_burst_threshold" class="form-label">Download Burst Threshold (Mbps)</label>
                                    <input type="number" class="form-control @error('download_burst_threshold') is-invalid @enderror" 
                                           id="download_burst_threshold" name="download_burst_threshold" value="{{ old('download_burst_threshold') }}" 
                                           min="1" step="0.1">
                                    @error('download_burst_threshold')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="upload_burst_threshold" class="form-label">Upload Burst Threshold (Mbps)</label>
                                    <input type="number" class="form-control @error('upload_burst_threshold') is-invalid @enderror" 
                                           id="upload_burst_threshold" name="upload_burst_threshold" value="{{ old('upload_burst_threshold') }}" 
                                           min="1" step="0.1">
                                    @error('upload_burst_threshold')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="download_burst_time" class="form-label">Download Burst Time (seconds)</label>
                                    <input type="number" class="form-control @error('download_burst_time') is-invalid @enderror" 
                                           id="download_burst_time" name="download_burst_time" value="{{ old('download_burst_time') }}" 
                                           min="1">
                                    @error('download_burst_time')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="upload_burst_time" class="form-label">Upload Burst Time (seconds)</label>
                                    <input type="number" class="form-control @error('upload_burst_time') is-invalid @enderror" 
                                           id="upload_burst_time" name="upload_burst_time" value="{{ old('upload_burst_time') }}" 
                                           min="1">
                                    @error('upload_burst_time')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="priority" class="form-label">Priority (1-8, where 1 is highest)</label>
                                    <input type="number" class="form-control @error('priority') is-invalid @enderror" 
                                           id="priority" name="priority" value="{{ old('priority', 8) }}" 
                                           min="1" max="8">
                                    @error('priority')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="comment" class="form-label">Comment</label>
                                    <textarea class="form-control @error('comment') is-invalid @enderror" 
                                              id="comment" name="comment" rows="3">{{ old('comment') }}</textarea>
                                    @error('comment')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-between">
                                    <a href="{{ route('isp.bandwidth-profiles.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Back
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Create Bandwidth Profile
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-fill burst thresholds based on burst limits
    const downloadBurstLimit = document.getElementById('download_burst_limit');
    const uploadBurstLimit = document.getElementById('upload_burst_limit');
    const downloadBurstThreshold = document.getElementById('download_burst_threshold');
    const uploadBurstThreshold = document.getElementById('upload_burst_threshold');
    const downloadLimit = document.getElementById('download_limit');
    const uploadLimit = document.getElementById('upload_limit');

    function autoFillThresholds() {
        if (downloadBurstLimit.value && !downloadBurstThreshold.value) {
            downloadBurstThreshold.value = (parseFloat(downloadBurstLimit.value) * 0.8).toFixed(1);
        }
        if (uploadBurstLimit.value && !uploadBurstThreshold.value) {
            uploadBurstThreshold.value = (parseFloat(uploadBurstLimit.value) * 0.8).toFixed(1);
        }
    }

    downloadBurstLimit.addEventListener('input', autoFillThresholds);
    uploadBurstLimit.addEventListener('input', autoFillThresholds);
});
</script>
@endpush
