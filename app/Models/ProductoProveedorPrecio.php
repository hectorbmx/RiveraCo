<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductoProveedorPrecio extends Model
{
    protected $table = 'producto_proveedor_precios';

    protected $fillable = [
        'producto_id','proveedor_id','precio','moneda','orden_compra_id'
    ];
}
