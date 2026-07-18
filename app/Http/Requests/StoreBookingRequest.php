<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $nightSlots = ['night_4lights', 'night_2lights'];
        $isNight = in_array($this->input('slot_type'), $nightSlots, true);

        return [
            'full_name'     => ['required', 'string', 'max:255'],
            'mobile_number' => ['required', 'string', 'max:20', 'regex:/^[0-9+\s-]{7,20}$/'],
            'nic'           => ['required', 'string', 'max:20'],
            'email'         => ['nullable', 'email', 'max:255'],
            'purpose'       => ['required', 'string', 'max:255'],
            'slot_type'     => ['required', 'string', Rule::in(['full_day', 'daytime', 'night_4lights', 'night_2lights'])],
            'start_time'    => [$isNight ? 'required' : 'nullable', 'date_format:H:i'],
            'end_time'      => [$isNight ? 'required' : 'nullable', 'date_format:H:i'],
            'hours'         => [$isNight ? 'required' : 'nullable', 'integer', 'min:1', 'max:12'],
            'chair_count'   => ['nullable', 'integer', 'min:0', 'max:9999'],
            'sound_system_requested' => ['nullable', 'boolean'],
            'receipt_file'  => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'dates'         => ['required', 'array', 'min:1', 'max:31'],
            'dates.*'       => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'nic.required'          => 'Please enter your NIC number.',
            'slot_type.required'    => 'Please select a time slot.',
            'slot_type.in'          => 'Please select a valid time slot.',
            'start_time.required'   => 'Please enter the start time for your session.',
            'end_time.required'     => 'Please enter the end time for your session.',
            'hours.required'        => 'Please specify how many hours you need.',
            'receipt_file.mimes'    => 'Receipt must be a JPG, PNG, or PDF file.',
            'receipt_file.max'      => 'Receipt file must be under 5 MB.',
            'dates.required'        => 'Please select at least one date.',
            'dates.*.after_or_equal'=> 'You can only book from today onward.',
            'mobile_number.regex'   => 'Please enter a valid mobile number.',
        ];
    }
}
