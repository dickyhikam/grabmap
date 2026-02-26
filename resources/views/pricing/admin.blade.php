<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Pricing Admin - GrabMaps</title>

    <link rel="shortcut icon" href="{{ asset('logo2.png') }}" type="image/png">
    <link rel="icon" href="{{ asset('logo2.png') }}" type="image/png" sizes="32x32">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/pricing.css">

    <style>
        body {
            background: var(--bg-subtle);
        }
    </style>
</head>

<body>
    <nav class="admin-navbar">
        <div class="container">
            <h5><i class="bi bi-gear" style="color:var(--grab-green)"></i> Pricing Admin</h5>
            <a href="{{ route('pricing') }}" class="btn-link-nav">
                <i class="bi bi-arrow-left"></i> Back to Pricing
            </a>
        </div>
    </nav>

    <div class="content">
        @if(session('success'))
        <div class="flash-message flash-success">
            <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
        </div>
        @endif

        @foreach($categories as $category)
        <div class="category-card">
            <div class="category-header">
                <h5>{{ $category->name }}</h5>
                <span style="font-size:0.82rem;color:var(--text-muted);">{{ $category->items->count() }} items</span>
            </div>

            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>API Name</th>
                            <th>Tier</th>
                            <th>ALS Price</th>
                            <th>Google Price</th>
                            <th>Free Tier</th>
                            <th>ALS Only</th>
                            <th>Order</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($category->items as $item)
                        <tr id="row-{{ $item->id }}">
                            {{-- VIEW MODE --}}
                            <td class="view-cell">{{ $item->api_name }}</td>
                            <td class="view-cell">{{ $item->tier ?? '-' }}</td>
                            <td class="view-cell" style="font-weight:600;color:var(--grab-green);">
                                {{ $item->als_price !== null ? '$' . number_format((float)$item->als_price, 4) : '-' }}
                            </td>
                            <td class="view-cell" style="font-weight:600;color:#4285F4;">
                                {{ $item->google_price !== null ? '$' . number_format((float)$item->google_price, 4) : '-' }}
                            </td>
                            <td class="view-cell">{{ $item->google_free_threshold > 0 ? number_format($item->google_free_threshold) : '-' }}</td>
                            <td class="view-cell">{{ $item->als_only ? 'Yes' : 'No' }}</td>
                            <td class="view-cell">{{ $item->sort_order }}</td>
                            <td class="view-cell">
                                <div class="actions-cell">
                                    <button class="btn-sm-action btn-edit" onclick="toggleEdit({{ $item->id }})">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form action="{{ route('pricing.destroy', $item) }}" method="POST"
                                        onsubmit="return confirm('Delete this item?');" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-sm-action btn-delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        {{-- EDIT MODE ROW --}}
                        <tr id="edit-{{ $item->id }}" style="display:none;background:var(--grab-green-light);">
                            <form action="{{ route('pricing.update', $item) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="pricing_category_id" value="{{ $category->id }}">
                                <td>
                                    <input type="text" name="api_name" value="{{ $item->api_name }}" required>
                                </td>
                                <td>
                                    <input type="text" name="tier" value="{{ $item->tier }}" placeholder="e.g. Core">
                                </td>
                                <td>
                                    <input type="number" name="als_price" value="{{ $item->als_price }}" step="0.0001" min="0">
                                </td>
                                <td>
                                    <input type="number" name="google_price" value="{{ $item->google_price }}" step="0.0001" min="0">
                                </td>
                                <td>
                                    <input type="number" name="google_free_threshold" value="{{ $item->google_free_threshold }}" min="0">
                                </td>
                                <td>
                                    <input type="checkbox" name="als_only" value="1" {{ $item->als_only ? 'checked' : '' }}>
                                </td>
                                <td>
                                    <input type="number" name="sort_order" value="{{ $item->sort_order }}" min="0" style="width:60px;">
                                </td>
                                <td>
                                    <div class="actions-cell">
                                        <button type="submit" class="btn-sm-action btn-save">Save</button>
                                        <button type="button" class="btn-sm-action btn-cancel" onclick="toggleEdit({{ $item->id }})">Cancel</button>
                                    </div>
                                </td>
                            </form>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- ADD FORM --}}
            <button class="btn-add" onclick="toggleAddForm('add-form-{{ $category->slug }}')">
                <i class="bi bi-plus-circle"></i> Add Item to {{ $category->name }}
            </button>

            <div class="add-form" id="add-form-{{ $category->slug }}">
                <form action="{{ route('pricing.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="pricing_category_id" value="{{ $category->id }}">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label>API Name *</label>
                            <input type="text" name="api_name" required placeholder="e.g. Calculate Routes">
                        </div>
                        <div class="col-md-2">
                            <label>Tier</label>
                            <input type="text" name="tier" placeholder="e.g. Core">
                        </div>
                        <div class="col-md-2">
                            <label>ALS Price (per 1K)</label>
                            <input type="number" name="als_price" step="0.0001" min="0" placeholder="0.5000">
                        </div>
                        <div class="col-md-2">
                            <label>Google Price (per 1K)</label>
                            <input type="number" name="google_price" step="0.0001" min="0" placeholder="5.0000">
                        </div>
                        <div class="col-md-1">
                            <label>Free Tier</label>
                            <input type="number" name="google_free_threshold" min="0" value="0">
                        </div>
                        <div class="col-md-1">
                            <label>Order</label>
                            <input type="number" name="sort_order" min="0" value="0">
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <label class="d-flex align-items-center gap-1">
                                <input type="checkbox" name="als_only" value="1"> ALS Only
                            </label>
                        </div>
                    </div>
                    <div class="row g-2 mt-1">
                        <div class="col-md-6">
                            <label>Notes</label>
                            <input type="text" name="notes" placeholder="Optional notes">
                        </div>
                        <div class="col-md-6 d-flex align-items-end gap-2">
                            <button type="submit" class="btn-sm-action btn-save" style="padding:8px 20px;">
                                <i class="bi bi-plus"></i> Add Item
                            </button>
                            <button type="button" class="btn-sm-action btn-cancel" style="padding:8px 20px;"
                                onclick="toggleAddForm('add-form-{{ $category->slug }}')">
                                Cancel
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        @endforeach
    </div>

    <script>
        function toggleEdit(id) {
            const viewRow = document.getElementById('row-' + id);
            const editRow = document.getElementById('edit-' + id);

            if (editRow.style.display === 'none') {
                editRow.style.display = 'table-row';
                viewRow.style.display = 'none';
            } else {
                editRow.style.display = 'none';
                viewRow.style.display = 'table-row';
            }
        }

        function toggleAddForm(formId) {
            const form = document.getElementById(formId);
            form.classList.toggle('active');
        }
    </script>
</body>

</html>