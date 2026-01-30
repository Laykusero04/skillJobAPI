<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Carbon\Carbon;

class StoreGigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 2;
    }

    public function rules(): array
    {
        return [
            'title'               => ['required', 'string', 'max:255'],
            'primary_skill_id'    => ['required', 'integer', 'exists:skills,id'],
            'supporting_skill_ids' => ['nullable', 'array'],
            'supporting_skill_ids.*' => ['integer', 'exists:skills,id', 'distinct'],
            'location'            => ['required', 'string', 'max:255'],
            'date'                => ['required', 'date'],
            'start_time'          => ['required', 'date_format:H:i'],
            'end_time'            => ['required', 'date_format:H:i'],
            'pay'                 => ['required', 'numeric', 'gt:0'],
            'workers_needed'      => ['required', 'integer', 'min:1'],
            'description'         => ['required', 'string', 'max:300'],
            'auto_close_enabled'  => ['nullable', 'boolean'],
            'auto_close_date'     => ['nullable', 'required_if:auto_close_enabled,true', 'date'],
            'auto_close_time'     => ['nullable', 'required_if:auto_close_enabled,true', 'date_format:H:i'],
            'latitude'            => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude'           => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            'app_saving_percent'  => ['nullable', 'integer', 'between:0,100'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $startAt = Carbon::parse($this->date . ' ' . $this->start_time);
                $endAt = Carbon::parse($this->date . ' ' . $this->end_time);

                if ($startAt->isPast()) {
                    $validator->errors()->add('start_time', 'The gig start time must be in the future.');
                }

                if ($endAt->lte($startAt)) {
                    $validator->errors()->add('end_time', 'The end time must be after the start time.');
                }

                if ($this->auto_close_enabled && $this->auto_close_date && $this->auto_close_time) {
                    $autoCloseAt = Carbon::parse($this->auto_close_date . ' ' . $this->auto_close_time);

                    if ($autoCloseAt->isPast()) {
                        $validator->errors()->add('auto_close_date', 'The auto-close time must be in the future.');
                    }

                    if ($autoCloseAt->gte($startAt)) {
                        $validator->errors()->add('auto_close_date', 'The auto-close time must be before the gig start time.');
                    }
                }

                if ($this->supporting_skill_ids && in_array($this->primary_skill_id, $this->supporting_skill_ids)) {
                    $validator->errors()->add('supporting_skill_ids', 'Supporting skills must not include the primary skill.');
                }
            },
        ];
    }
}
