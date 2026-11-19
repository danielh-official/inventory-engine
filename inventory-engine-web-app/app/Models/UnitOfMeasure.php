<?php

namespace App\Models;

use Database\Factories\UnitOfMeasureFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Table('units_of_measure')]
#[Fillable(['code', 'name'])]
class UnitOfMeasure extends Model
{
    /** @use HasFactory<UnitOfMeasureFactory> */
    use HasFactory;
}
