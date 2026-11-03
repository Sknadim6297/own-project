<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\AuthorizesToolRegistry;
use App\Http\Controllers\Controller;
use App\Models\NutritionDuplicateReview;
use App\Models\NutritionImportBatch;
use App\Models\NutritionImportRow;
use Illuminate\Http\Request;

class NutritionImportBatchController extends Controller
{
    use AuthorizesToolRegistry;

    public function index()
    {
        $this->authorizeToolRegistry('tools_management');

        $batches = NutritionImportBatch::query()
            ->where('is_dry_run', false)
            ->orderByDesc('id')
            ->paginate(20);

        return view('admin.nutrition.imports.index', compact('batches'));
    }

    public function show(NutritionImportBatch $nutritionImportBatch)
    {
        $this->authorizeToolRegistry('tools_management');

        $rows = NutritionImportRow::query()
            ->where('nutrition_import_batch_id', $nutritionImportBatch->id)
            ->orderBy('worksheet_row')
            ->paginate(50);

        return view('admin.nutrition.imports.show', [
            'batch' => $nutritionImportBatch,
            'rows' => $rows,
        ]);
    }

    public function duplicates(Request $request)
    {
        $this->authorizeToolRegistry('tools_management');

        $duplicates = NutritionDuplicateReview::query()
            ->with(['food', 'candidate'])
            ->when($request->query('decision'), fn ($q, $d) => $q->where('decision', $d))
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.nutrition.imports.duplicates', compact('duplicates'));
    }

    public function decideDuplicate(Request $request, NutritionDuplicateReview $duplicate)
    {
        $this->authorizeToolRegistry('tools_management');
        $data = $request->validate([
            'decision' => ['required', 'in:retain_separate,reject_import,merge_later'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $duplicate->update([
            'decision' => $data['decision'],
            'notes' => $data['notes'] ?? null,
            'decided_by_admin_id' => auth('admin')->id(),
        ]);

        return back()->with('success', 'Duplicate decision saved. Automatic merges are not performed.');
    }
}
