<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Carbon\Carbon;

class UpdateGigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 2
            && $this->route('gig')->employer_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'title'               => ['sometimes', 'string', 'max:255'],
            'primary_skill_id'    => ['sometimes', 'integer', 'exists:skills,id'],
            'supporting_skill_ids' => ['nullable', 'array'],
            'supporting_skill_ids.*' => ['integer', 'exists:skills,id', 'distinct'],
            'location'            => ['sometimes', 'string', 'max:255'],
            'date'                => ['sometimes', 'date'],
            'start_time'          => ['sometimes', 'date_format:H:i'],
            'end_time'            => ['sometimes', 'date_format:H:i'],
            'pay'                 => ['sometimes', 'numeric', 'gt:0'],
            'workers_needed'      => ['sometimes', 'integer', 'min:1'],
            'description'         => ['sometimes', 'string', 'max:300'],
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

                $gig = $this->route('gig');

                $date = $this->date ?? $gig->start_at->toDateString();
                $startTime = $this->start_time ?? $gig->start_at->format('H:i');
                $endTime = $this->end_time ?? $gig->end_at->format('H:i');

                $startAt = Carbon::parse($date . ' ' . $startTime);
                $endAt = Carbon::parse($date . ' ' . $endTime);

                if ($endAt->lte($startAt)) {
                    $validator->errors()->add('end_time', 'The end time must be after the start time.');
                }

                if ($this->auto_close_enabled && $this->auto_close_date && $this->auto_close_time) {
                    $autoCloseAt = Carbon::parse($this->auto_close_date . ' ' . $this->auto_close_time);

                    if ($autoCloseAt->gte($startAt)) {
                        $validator->errors()->add('auto_close_date', 'The auto-close time must be before the gig start time.');
                    }
                }

                if ($this->has('workers_needed')) {
                    $acceptedCount = $gig->applications()
                        ->where('status', 'accepted')
                        ->count();

                    if ($this->workers_needed < $acceptedCount) {
                        $validator->errors()->add('workers_needed', 'Workers needed cannot be less than the number of accepted applicants (' . $acceptedCount . ').');
                    }
                }

                $primarySkillId = $this->primary_skill_id ?? $gig->primary_skill_id;
                if ($this->supporting_skill_ids && in_array($primarySkillId, $this->supporting_skill_ids)) {
                    $validator->errors()->add('supporting_skill_ids', 'Supporting skills must not include the primary skill.');
                }
            },
        ];
    }
}
