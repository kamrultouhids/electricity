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

@php $expense = $expense ?? null; @endphp

<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">Category <span class="text-danger">*</span></label>
        <select name="expense_category_id" class="form-select" required>
            <option value="">Select Category</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected(old('expense_category_id', $expense->expense_category_id ?? '') == $category->id)>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Amount <span class="text-danger">*</span></label>
        <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required
               placeholder="Enter Amount" value="{{ old('amount', $expense->amount ?? '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Date <span class="text-danger">*</span></label>
        <input type="date" name="expense_date" class="form-control" required
               value="{{ old('expense_date', isset($expense) ? $expense->expense_date->format('Y-m-d') : now()->format('Y-m-d')) }}">
    </div>
    <div class="col-md-12">
        <label class="form-label">Note</label>
        <input type="text" name="note" class="form-control" placeholder="Optional"
               value="{{ old('note', $expense->note ?? '') }}">
    </div>
</div>

<div class="mt-4 text-end">
    <button type="submit" class="btn btn-primary text-white">{{ $expense ? 'Update' : 'Save' }}</button>
    <a href="{{ route('expenses.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>
