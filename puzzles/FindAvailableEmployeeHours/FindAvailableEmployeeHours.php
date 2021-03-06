<?php 
class EmployeeHours { 
    public $default_hours           = [];
    public $company_holidays        = [];
    public $employees               = [];
    public $employee_data           = [];
    public $employee_availabilities = [];
    public $raw_file;
    public $cleaned_file;

    public function get_raw_file_contents() 
    {
        $this->raw_file = file_get_contents('input.txt');
    }

    public function clean_file() 
    {
        //Remove empty new lines & trailing spaces & header
        $this->raw_file = preg_replace('/^[ \t]*[\r\n]+/m', '', $this->raw_file);
        $cleaned_file  = explode("\n", $this->raw_file);
        $this->cleaned_file = array_filter($cleaned_file);
    }

    public function process_file() 
    {
        //Initialize variables for processing file
        $populate_employees    = false;
        $populate_availability = false;
        $employee_headers      = [];
        foreach($this->cleaned_file as $key => $value)
        {   
            if (!empty($value) && $value != "\n") {
                //Remove all titles with availability in the name
                if (strpos($value, 'availability') !== false) {
                    continue;
                }

                //Populate $default_hours
                if (strpos($value, '# company work hours') !== false) {
                    $this->default_hours = explode(',', $this->cleaned_file[$key+1]);
                }

                //Populate $company_holidays
                if (strpos($value, '# company holidays') !== false) {
                    $this->company_holidays = explode(',', $this->cleaned_file[$key+1]);
                }

                //Populate $employees for unlisted extra credit
                if (strpos($value, '#') !== 9
                    && ctype_alpha(substr($value, -2, -1)) 
                    && substr($value, -2, -1) !== ']' 
                    && substr($value, 0, 1) !== '"'
                    ) {
                    if (strpos($value, '# employees') !== false || $populate_employees == true) {
                        if (strpos($value, '#') !== false && strpos($value, '# employees') !== 0) {
                            $populate_employees = false;
                            continue;
                        }
                        if (strpos($value, '# employees') !== 0) {
                            $row = explode(',', $this->cleaned_file[$key]);
                            $this->employees[$row[0]] = $row[1];
                        }
                        $populate_employees = true;
                    }
                }

                //Run remaining rows through find_availabile_work_hours
                if ((substr($value, -2, -1) == ']'
                    || substr($value, -2, -1) == '"')
                    && substr($value, 0, 1) !== '"'
                ) {
                    //Get employee id for organization sake
                    $sub_val = explode(',', $value);

                    //Seperate hours from non-hours 
                    $hours = trim(substr($value, strpos($value, ',[')));
                    $value = str_replace($hours, '', $value);

                    //Clean the hours array
                    $hours = ltrim($hours, ',');
                    $hours = rtrim($hours, ']');
                    $hours = ltrim($hours, '[');
                    if (strpos($hours, '"') !== false) {
                        $hours = null;
                    }

                    //Populate array to cycle through find_availabile_work_hours
                    $this->employee_data[] = 
                    [
                        $sub_val[0], //Employee ID
                        $sub_val[1], //From
                        $sub_val[2] === 'null' ? null : $sub_val[2], //To
                        !empty($hours) ? explode(',', $hours) : null //Hours array
                    ];
                }
            }
        }
    }

    public function process_employee_data() 
    {
        foreach($this->employee_data as $data) {
            $this->find_availabile_work_hours(
                $data[0], 
                $data[1], 
                $data[2], 
                $data[3]
            );
        }
    }

    public function find_availabile_work_hours($employee_id, $from, $to, $override = null) 
    {
        //Remove quotes
        $from = str_replace('"', '', $from);
        $to   = str_replace('"', '', $to);
        foreach($this->company_holidays as $holiday) {
            $this->company_holidays[] = str_replace('"', '', $holiday);
        }

        //Handle a null $to seperately from non-null
        if (!empty($to)) {  
            $max = abs(floor((strtotime($from) - strtotime($to))/86400))+1; 
            //Loop from $from to $to
            for($i = 0; $i < $max; $i++) {
                $date        = strtotime($from . ' + ' . $i . ' days');
                $day_of_week = date('w', $date);
                $hour        = !empty($override) ? $override[$day_of_week] : $this->default_hours[$day_of_week];
                if (in_array(substr(date('m/d/Y', $date), 0, 5), $this->company_holidays)) {
                    $hour = 0;
                }
                echo '<p>"' . date('m/d/Y', $date) . '", ' . $hour .' </p>';
            }
        } else {
            $date        = strtotime($from);
            $day_of_week = date('w', $date);
            $hour        = !empty($override) ? $override[$day_of_week] : $this->default_hours[$day_of_week];
            echo '<p>"' . date('m/d/Y', $date) . '", ' . $hour .' </p>';
        }
    }
} 
$employee_hours = new EmployeeHours;
$employee_hours->get_raw_file_contents();
$employee_hours->clean_file();
$employee_hours->process_file();
$employee_hours->process_employee_data();
?> 
