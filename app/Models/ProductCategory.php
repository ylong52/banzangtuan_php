<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    protected $table = 'product_category';
    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $fillable = [
        'category', 'parent_id', 'brands', 'img', 'level', 'sort', 'status', 'created_at', 'updated_at'
    ]; 
    

   
} 