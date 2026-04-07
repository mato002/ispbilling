@extends('layouts.admin')

@section('title', 'Portal Settings')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-wifi"></i>
                        Hotspot Portal Settings
                    </h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.portal-settings.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h4>Basic Settings</h4>
                                
                                <div class="form-group mb-3">
                                    <label for="welcome_message">Welcome Message</label>
                                    <input type="text" class="form-control @error('welcome_message') is-invalid @enderror" 
                                           id="welcome_message" name="welcome_message" 
                                           value="{{ old('welcome_message', $portalSetting->welcome_message) }}" 
                                           placeholder="Welcome to WiFi">
                                    @error('welcome_message')
                                        <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group mb-3">
                                    <label for="logo">Logo</label>
                                    <input type="file" class="form-control @error('logo') is-invalid @enderror" 
                                           id="logo" name="logo" accept="image/*">
                                    @error('logo')
                                        <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                    @enderror
                                    @if($portalSetting->logo_url)
                                        <div class="mt-2">
                                            <img src="{{ $portalSetting->logo_url }}" alt="Current Logo" class="img-thumbnail" style="max-height: 60px;">
                                            <small class="text-muted d-block">Current logo</small>
                                        </div>
                                    @endif
                                </div>

                                <div class="form-group mb-3">
                                    <label for="logo_url">Or Logo URL</label>
                                    <input type="url" class="form-control @error('logo_url') is-invalid @enderror" 
                                           id="logo_url" name="logo_url" 
                                           value="{{ old('logo_url', $portalSetting->logo_url) }}" 
                                           placeholder="https://example.com/logo.png">
                                    @error('logo_url')
                                        <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <h4>Appearance</h4>
                                
                                <div class="form-group mb-3">
                                    <label for="background_type">Background Type</label>
                                    <select class="form-control @error('background_type') is-invalid @enderror" 
                                            id="background_type" name="background_type">
                                        <option value="gradient" {{ old('background_type', $portalSetting->background_type) == 'gradient' ? 'selected' : '' }}>Gradient</option>
                                        <option value="image" {{ old('background_type', $portalSetting->background_type) == 'image' ? 'selected' : '' }}>Image</option>
                                        <option value="color" {{ old('background_type', $portalSetting->background_type) == 'color' ? 'selected' : '' }}>Solid Color</option>
                                    </select>
                                    @error('background_type')
                                        <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group mb-3">
                                    <label for="background_value">Background Value</label>
                                    <textarea class="form-control @error('background_value') is-invalid @enderror" 
                                              id="background_value" name="background_value" rows="3">{{ old('background_value', $portalSetting->background_value) }}</textarea>
                                    <small class="form-text text-muted">
                                        For gradient: CSS gradient (e.g., linear-gradient(135deg, #667eea 0%, #764ba2 100%))<br>
                                        For image: Image URL<br>
                                        For color: Hex color (e.g., #667eea)
                                    </small>
                                    @error('background_value')
                                        <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group mb-3">
                                    <label for="theme_color">Theme Color</label>
                                    <input type="color" class="form-control @error('theme_color') is-invalid @enderror" 
                                           id="theme_color" name="theme_color" 
                                           value="{{ old('theme_color', $portalSetting->theme_color) }}">
                                    @error('theme_color')
                                        <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <h4>Content Settings</h4>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" 
                                                       id="show_packages" name="show_packages" 
                                                       value="1" {{ old('show_packages', $portalSetting->show_packages) ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="show_packages">
                                                    Show Packages
                                                </label>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" 
                                                       id="show_payment_button" name="show_payment_button" 
                                                       value="1" {{ old('show_payment_button', $portalSetting->show_payment_button) ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="show_payment_button">
                                                    Show Payment Button
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="payment_button_text">Payment Button Text</label>
                                            <input type="text" class="form-control @error('payment_button_text') is-invalid @enderror" 
                                                   id="payment_button_text" name="payment_button_text" 
                                                   value="{{ old('payment_button_text', $portalSetting->payment_button_text) }}" 
                                                   placeholder="Buy Now">
                                            @error('payment_button_text')
                                                <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <h4>Preview</h4>
                                <div class="border rounded p-3" style="background: {{ $portalSetting->background_value }};">
                                    <div class="text-center text-white">
                                        @if($portalSetting->logo_url)
                                            <img src="{{ $portalSetting->logo_url }}" alt="Logo" class="h-12 mb-2" style="filter: brightness(0) invert(1);">
                                        @endif
                                        <h3>{{ $portalSetting->welcome_message }}</h3>
                                        @if($portalSetting->show_packages)
                                            <p class="mb-3">Choose your internet package.</p>
                                        @endif
                                        @if($portalSetting->show_payment_button)
                                            <button class="btn" style="background-color: {{ $portalSetting->theme_color }}; color: white;">
                                                {{ $portalSetting->payment_button_text }}
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i>
                                    Save Settings
                                </button>
                                <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i>
                                    Cancel
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
