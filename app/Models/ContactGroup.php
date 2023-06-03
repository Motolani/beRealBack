<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactGroup extends Model
{
    use HasFactory;
    protected $table = "contact_groups";

    protected $fillable = [
	    'name','user_ids','user_count',
	];
}
