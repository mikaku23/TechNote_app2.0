<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;
    use SoftDeletes;
    protected $fillable = [
        'role_id',
        'name',
        'username',
        'email',
        'nim',
        'nip',
        'no_hp',
        'password',
        'security_question',
        'security_answer',
        'qr_code',
        'qr_url',
        'avatar',
        'last_login_at',
        'last_password_changed_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'last_password_changed_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function role()
    {
        return $this->belongsTo(role::class);
    }

    public function tickets()
    {
        return $this->hasMany(ticket::class);
    }

    public function penginstalans()
    {
        return $this->hasMany(penginstalan::class);
    }

    public function perbaikans()
    {
        return $this->hasMany(perbaikan::class);
    }

    public function ticketComments()
    {
        return $this->hasMany(ticket_comment::class);
    }

    public function notifications()
    {
        return $this->hasMany(notification::class);
    }

    public function aiLogs()
    {
        return $this->hasMany(ai_log::class);
    }

    public function aiTasks()
    {
        return $this->hasMany(ai_task::class);
    }

    public function contacts()
    {
        return $this->hasMany(contact::class);
    }

    public function loginLogs()
    {
        return $this->hasMany(login_log::class);
    }

    public function userActivities()
    {
        return $this->hasMany(user_activitie::class);
    }

    public function passwordResetOtps()
    {
        return $this->hasMany(password_reset_otp::class);
    }
}
