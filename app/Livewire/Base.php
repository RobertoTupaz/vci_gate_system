<?php

namespace App\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class Base extends Component
{
    public $qrInput = '';
    public $student;
    public $attendance;
    public $message = '';
    public $recent = [];

    /**
     * Automatically called after $qrInput changes (debounced from Blade)
     */
    #[On('qrScanned')]
    public function updatedQrInput($value, $action)
    {
        if (!empty($value)) {
            if ($action === 'in') {
                $this->timeIn($value);
            } elseif ($action === 'out') {
                $this->timeOut($value);
            } else {
                $this->message = 'Invalid action.';
            }

            $this->qrInput = ''; // Clear field after submit
        }
    }

    public function timeIn($qr)
    {
        $user = User::where('StudentNumber', $qr)->first();

        if (!$user) {
            $this->resetStudentData('Student not found.');
            return;
        }

        $this->student = $user;
        $today = Carbon::today();

        // Always create a new Time In entry
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $today,
            'time_in' => now(),
        ]);

        $this->message = 'Time In recorded.';
        $this->attendance = $attendance;
        $this->loadRecent();
    }

    public function timeOut($qr)
    {
        $user = User::where('StudentNumber', $qr)->first();

        if (!$user) {
            $this->resetStudentData('Student not found.');
            return;
        }

        $this->student = $user;
        $today = Carbon::today();

        // Find the last record for today with no time out
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->whereNull('time_out')
            ->latest()
            ->first();

        if ($attendance) {
            $attendance->update([
                'time_out' => now(),
            ]);
            $this->message = 'Time Out recorded.';
        } else {
            // No time in found, create a new record with only time out
            $attendance = Attendance::create([
                'user_id' => $user->id,
                'date' => $today,
                'time_out' => now(),
            ]);
            $this->message = 'Time Out recorded (no Time In found).';
        }

        $this->attendance = $attendance?->fresh();
        $this->loadRecent();
    }

    /**
     * Process QR Code and record attendance
     */
    public function processQr($qr, $action)
    {
        $user = User::where('StudentNumber', $qr)->first();

        if (!$user) {
            $this->resetStudentData('Student not found.');
            return;
        }

        $this->student = $user;

        $today = Carbon::today();
        // Get the latest attendance record for today
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->latest()
            ->first();

        if (!$attendance) {
            // No record yet for today → create time in
            $attendance = Attendance::create([
                'user_id' => $user->id,
                'date' => $today,
                'time_in' => now(),
            ]);
            $this->message = 'Time In recorded.';
        } elseif (!$attendance->time_out) {
            // Has time in but no time out → record time out
            $attendance->update([
                'time_out' => now(),
            ]);
            $this->message = 'Time Out recorded.';
        } else {
            // Has both time in & out → create a NEW time in
            $attendance = Attendance::create([
                'user_id' => $user->id,
                'date' => $today,
                'time_in' => now(),
            ]);
            $this->message = 'New Time In recorded.';
        }

        $this->attendance = $attendance->fresh();
        $this->loadRecent();
    }

    /**
     * Clear student & attendance data with message
     */
    protected function resetStudentData($message)
    {
        $this->student = null;
        $this->attendance = null;
        $this->message = $message;
    }

    /**
     * Load recent attendance records for sidebar
     */
    public function loadRecent()
    {
        $this->recent = Attendance::with('user')
            ->latest('updated_at')
            ->take(2)
            ->get();
    }

    public function mount()
    {
        $this->loadRecent();
    }

    public function render()
    {
        return view('livewire.base');
    }
}
