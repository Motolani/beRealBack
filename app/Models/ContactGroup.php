<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactGroup extends Model
{
    use HasFactory;
    protected $table = "contact_groups";

    protected $fillable = [
	    'name','contact_ids','contact_count','user_id','soft_delete'
	];
}
