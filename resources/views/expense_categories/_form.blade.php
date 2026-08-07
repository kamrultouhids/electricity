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

@php $category = $category ?? null; @endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control" required placeholder="Enter Category Name"
               value="{{ old('name', $category->name ?? '') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">Status <span class="text-danger">*</span></label>
        <select name="status" class="form-select" required>
            <option value="1" @selected(old('status', $category->status ?? 1) == 1)>Active</option>
            <option value="0" @selected(old('status', $category->status ?? 1) == 0)>Inactive</option>
        </select>
    </div>
</div>

<div class="mt-4 text-end">
    <button type="submit" class="btn btn-primary text-white">{{ $category ? 'Update' : 'Save' }}</button>
    <a href="{{ route('expense-categories.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>
