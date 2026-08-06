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

    @unless ($user)
        <div class="col-md-4">
            <label class="form-label">Password <span class="text-danger">*</span></label>
            <input type="password" name="password" class="form-control" placeholder="Enter Password"
                   required autocomplete="new-password">
        </div>
        <div class="col-md-4">
            <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
            <input type="password" name="password_confirmation" class="form-control" placeholder="Confirm Password"
                   required autocomplete="new-password">
        </div>
    @endunless
</div>

<div class="mt-4 text-end">
    <button type="submit" class="btn btn-primary text-white">{{ $user ? 'Update' : 'Save' }}</button>
    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>
