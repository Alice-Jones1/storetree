<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class dobRequired implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $day = request()->input('day');
        $month = request()->input('month');
        $year = request()->input('year');

        if (empty($day) && empty($month) && empty($year)) {
            return false;
        }
        if (empty($day) || empty($month) || empty($year)) {
            return false;
        }
        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        $day = request()->input('day');
        $month = request()->input('month');
        $year = request()->input('year');


        if (empty($day) && empty($month) && empty($year)) {
            return 'Date Of Birth is Required';
        }
        if (empty($day) || empty($month) || empty($year)) {
            return 'Please enter a valid Date of Birth';
        }
        return 'Validation failed.';
    }
}
