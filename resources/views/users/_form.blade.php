@csrf

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if (session('import_errors'))
        <div class="alert alert-warning">
            <div class="fw-semibold mb-1">Some rows were skipped:</div>
            <ul class="mb-0 small">
                @foreach (session('import_errors') as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

@php $user = $user ?? null; @endphp

<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control" required placeholder="Enter Name"
               value="{{ old('name', $user->name ?? '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Email <span class="text-danger">*</span></label>
        <input type="email" name="email" class="form-control" required placeholder="Enter Email"
               value="{{ old('email', $user->email ?? '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">User Type <span class="text-danger">*</span></label>
        <select name="user_type" class="form-select" required>
            <option value="">Select</option>
            @foreach ($userTypes as $value => $label)
                <option value="{{ $value }}" @selected(old('user_type', $user->user_type ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label">Status <span class="text-danger">*</span></label>
        <select name="status" class="form-select" required>
            <option value="1" @selected(old('status', $user->status ?? 1) == 1)>Active</option>
            <option value="0" @selected(old('status', $user->status ?? 1) == 0)>Inactive</option>
        </select>
    </div>

    {{-- On edit the password is optional: filled in only when it is being
         changed, left blank to keep the existing one. --}}
    <div class="col-md-4">
        <label class="form-label">
            Password @unless ($user)<span class="text-danger">*</span>@endunless
        </label>
        <input type="password" name="password" class="form-control"
               placeholder="{{ $user ? 'Leave blank to keep current' : 'Enter Password' }}"
               @unless ($user) required @endunless autocomplete="new-password">
        @if ($user)
            <div class="form-text">Leave both fields blank to keep the current password. Minimum 8 characters.</div>
        @endif
    </div>
    <div class="col-md-4">
        <label class="form-label">
            Confirm Password @unless ($user)<span class="text-danger">*</span>@endunless
        </label>
        <input type="password" name="password_confirmation" class="form-control"
               placeholder="{{ $user ? 'Repeat the new password' : 'Confirm Password' }}"
               @unless ($user) required @endunless autocomplete="new-password">
    </div>
</div>

<div class="mt-4 text-end">
    <button type="submit" class="btn btn-primary text-white">{{ $user ? 'Update' : 'Save' }}</button>
    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>
