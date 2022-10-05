<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class Customer extends Model
{
    const TABLE_FIELD_LOCATION_VALUE_UNKNOWN = 'Unknown';

    public $timestamps = false;

    protected $softDelete = false;

    public $fillable = [
        'name',
        'surname',
        'email',
        'age',
        'location',
        'country_code'
    ];

    protected $casts = [
        'id' => 'integer',
        'name' => 'string',
        'surname' => 'string',
        'email' => 'string',
        'age' => 'integer',
        'location' => 'string',
        'country_code' => 'string'
    ];

    /**
     * Set the customers's email.
     *
     * @param  string  $value
     *
     * @throws Exception on invalid email
     *
     * @return void
     */
    public function setEmailAttribute(string $value) : void
    {
        if (!$this->isValidEmail($value)) {
            throw new \Exception('email');
        }
        $this->attributes['email'] = $value;
    }

    /**
     * Set the customers's age.
     *
     * @param  int  $value
     *
     * @throws Exception on invalid age
     *
     * @return void
     */
    public function setAgeAttribute(int $value) : void
    {
        if (!$this->isValidAge($value)) {
            throw new \Exception('age');
        }
        $this->attributes['age'] = $value;
    }

    /**
     * Set the customers's location.
     *
     * @param  string  $value
     *
     * @return void
     */
    public function setLocationAttribute(string $value) : void
    {
        if (empty($value)) {
            $this->attributes['location'] = Self::TABLE_FIELD_LOCATION_VALUE_UNKNOWN;
        } else {
            $this->attributes['location'] = $value;
        }
    }

    /**
     * Check if email is valid.
     *
     * @param  string  $email
     *
     * @return boolean Result check
     */
    private function isValidEmail(string $email): bool
    {
        $validator = Validator::make(['email' => $email], [
            'email' => 'email:rfc,dns'
        ]);

        return !$validator->fails();
    }

    /**
     * Check if age is valid.
     *
     * @param  int     $age
     *
     * @return boolean Result check
     */
    private function isValidAge(int $age) : bool
    {
        $validator = Validator::make(['age' => $age], [
            'age' => 'integer|min:18|max:99'
        ]);

        return !$validator->fails();
    }
}
