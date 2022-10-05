<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Customer;

use Rap2hpoutre\FastExcel\FastExcel;

class MigrateCustomers extends Command
{
    const FIELD_KEY_ID = 'id';
    const FIELD_KEY_NAME = 'name';
    const FIELD_KEY_AGE = 'age';
    const FIELD_KEY_LOCATION = 'location';

    const TABLE_FIELD_NAME = 'name';
    const TABLE_FIELD_SURNAME = 'surname';
    const TABLE_FIELD_COUNTRY_CODE = 'country_code';

    const TABLE_FIELD_LOCATION_VALUE_UNKNOWN = 'Unknown';

    const ERROR_NO_CUSTOMERS = "There are no customers in the csv file\n";
    const SUCCESS_MIGRATION_FINISHED = "Customers migration was finished\n";
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'customers:migrate {csv_file_path} {excel_log_file_path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate customers from csv file';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $csv_file_path = $this->argument('csv_file_path');

        $customers = [];
        $field_keys = [];
        $countries = $this->getCountries();
        if (($open = fopen($csv_file_path, "r")) !== FALSE) {
            $str_counter = 0;
            while (($data = fgetcsv($open, 1000, ",")) !== FALSE) {
                if ($str_counter == 0) {
                    $field_keys = $data;
                } else {
                    $customer = $this->getCustomer($data, $field_keys, $countries);
                }
                if (isset($customer) && !empty($customer)) {
                    $customers[] = $customer;
                }
                $str_counter++;
            }
            fclose($open);
        }

        if (empty($customers)) {
            print_r(Self::ERROR_NO_CUSTOMERS);

            return;
        }

        $errors = [];
        $errors[] = array_merge($field_keys, ['error']);
        foreach ($customers as $key => $customer) {
            try {
                $this->saveCustomer($customer);
            } catch (\Throwable $e) {
                $errors[] = array_merge(array_values($customer['src_data']), [$e->getMessage()]);
            }
        }

        $this->saveErrorsToExcelFile($errors);

        print_r(Self::SUCCESS_MIGRATION_FINISHED);
        return 0;
    }

    /**
     * Get customer from source data.
     *
     * @param  array $data       Source data
     * @param  array $field_keys Source data field keys
     * @param  array $countries  Countries list
     *
     * @return array $customer   Prepared customer data
     */
    private function getCustomer(array $data, array $field_keys, array $countries) : array
    {
        $customer = [];

        foreach ($field_keys as $key => $field_key) {
            if ($field_key == Self::FIELD_KEY_NAME) {
                list($customer[Self::TABLE_FIELD_NAME], $customer[Self::TABLE_FIELD_SURNAME]) = explode(' ', $data[$key], 2);
            } else if ($field_key == Self::FIELD_KEY_AGE) {
                $customer[$field_key] = intval(str_replace(['\'', '"'], "", $data[$key]));
            } else if ($field_key != Self::FIELD_KEY_ID) {
                $customer[$field_key] = strval($data[$key]);
            }

            if ($field_key == Self::FIELD_KEY_LOCATION) {
                $country_code = $this->getCountyCode($countries, $data[$key]);
                $customer[Self::TABLE_FIELD_COUNTRY_CODE] = $country_code;
                if (empty($country_code)) {
                    $customer[$field_key] = '';
                }
            }
        }
        if (!empty($customer)) {
            $customer['src_data'] = $data;
        }

        return $customer;
    }

    /**
     * Save customer to DB.
     *
     * @param  array $customer Prepared customer data
     *
     * @throws Exception on empty email
     *
     * @return void
     */
    private function saveCustomer(array $customer) : void
    {
        if (empty($customer['email'])) {
            throw new \Exception('email');
        }

        if (Customer::where('email', $customer['email'])->count() > 0) {

            return;
        }

        Customer::create($customer);

        return;
    }

    /**
     * Save errors to excel file.
     *
     * @param  array $errors Errors
     *
     * @return void
     */
    private function saveErrorsToExcelFile(array $errors) : void
    {
        if (empty($errors)) {

            return;
        }

        (new FastExcel(collect($errors)))->export($this->argument('excel_log_file_path'));
    }

    /**
     * Get countries list as {country_name} => {country_iso3_code}.
     *
     * @return array $name_to_iso3 Countries array
     */
    private function getCountries() : array
    {
        $codes = json_decode(file_get_contents('http://country.io/iso3.json'), true);
        $names = json_decode(file_get_contents('http://country.io/names.json'), true);

        $name_to_iso3 = [];
        foreach($codes as $iso2 => $iso3) {
            $name_to_iso3[trim(strtolower($names[$iso2]))] = $iso3;
        }

        return $name_to_iso3;
    }

    /**
     * Get country code
     *
     * @param  array  $countries    Countries
     * @param  string $country_name Country name
     *
     * @return string Country ISO3 code
     */
    private function getCountyCode(array $countries, string $country_name) : string
    {

        return isset($countries[strtolower(trim($country_name))]) ? $countries[strtolower(trim($country_name))] : '';
    }
}
