@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Export Data Pelanggan</div>

                <div class="card-body">
                    <form action="{{ route('pelanggan.export') }}" method="POST">
                        @csrf
                        
                        <div class="form-group mb-3">
                            <label for="location">Pilih Lokasi:</label>
                            <select name="location" id="location" class="form-control">
                                <option value="">-- Semua Lokasi --</option>
                                @foreach($locations as $key => $value)
                                    <option value="{{ $key }}">{{ $value }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Export Excel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection