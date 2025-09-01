<?php

namespace App\Admin\Actions\Product;

use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;

class SoftDelete extends RowAction
{
    public $name = '软删除';

    public function handle(Model $model)
    {
        $model->deleted_at = now();
        $model->save();

        return $this->response()->success('软删除成功！')->refresh();
    }

    public function dialog()
    {
        $this->confirm('确定要软删除该商品吗？');
    }
}
