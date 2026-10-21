<?php
namespace App\Http\Controllers;
use App\Models\Category;

class CategoryController extends Controller {
    public function attributes(Category $category) {
        return $category->specs()->get(['id', 'name', 'unit', 'type', 'options']);
    }
}
