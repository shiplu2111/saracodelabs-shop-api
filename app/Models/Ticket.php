<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $guarded = [];

    // Relation: কার টিকেট?
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relation: সব রিপ্লাই বা চ্যাট
    public function replies()
    {
        return $this->hasMany(TicketReply::class);
    }
}
