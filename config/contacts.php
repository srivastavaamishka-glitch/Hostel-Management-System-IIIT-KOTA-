<?php
/**
 * Emergency & Administrative Contacts
 * IIIT Kota Hostel Management System
 * NOTE: All contact numbers and email addresses below are placeholder sample data for demonstration.
 */

declare(strict_types=1);

return [
    'boys' => [
        'warden' => [
            'designation' => 'Chief Warden (Boys Hostel)',
            'name'        => 'Dr. A. K. Sharma',
            'phone'       => '+91 98765 43210 (Sample)',
            'email'       => 'warden.boys@iiitkota.ac.in',
            'office'      => 'Room 101, Boys Hostel Block A',
        ],
        'caretaker' => [
            'designation' => 'Head Caretaker (Boys Hostel)',
            'name'        => 'Mr. Rajesh Verma',
            'phone'       => '+91 98765 43211 (Sample)',
            'email'       => 'caretaker.boys@iiitkota.ac.in',
            'office'      => 'Ground Floor Office, Boys Hostel Block B',
        ],
    ],
    'girls' => [
        'warden' => [
            'designation' => 'Chief Warden (Girls Hostel)',
            'name'        => 'Dr. Sunita Gupta',
            'phone'       => '+91 98765 43220 (Sample)',
            'email'       => 'warden.girls@iiitkota.ac.in',
            'office'      => 'Room G-02, Girls Hostel Block',
        ],
        'caretaker' => [
            'designation' => 'Head Caretaker (Girls Hostel)',
            'name'        => 'Mrs. Meena Kumari',
            'phone'       => '+91 98765 43221 (Sample)',
            'email'       => 'caretaker.girls@iiitkota.ac.in',
            'office'      => 'Reception Desk, Girls Hostel Block',
        ],
    ],
    'common' => [
        'dispensary' => [
            'designation' => 'College Dispensary / Health Center',
            'name'        => 'Dr. V. K. Mehta (Medical Officer)',
            'phone'       => '+91 98765 43299 (Sample 24x7)',
            'email'       => 'dispensary@iiitkota.ac.in',
            'office'      => 'Health Center, Main Campus Building',
        ],
        'ambulance' => [
            'designation' => 'Emergency Campus Ambulance',
            'name'        => 'Campus Medical Emergency Service',
            'phone'       => '+91 98765 43298 (Sample Emergency)',
            'email'       => 'emergency@iiitkota.ac.in',
            'office'      => 'Dispensary Parking Bay',
        ],
        'security' => [
            'designation' => 'Campus Main Gate Security Control',
            'name'        => 'Security Supervisor On Duty',
            'phone'       => '+91 98765 43200 (Sample)',
            'email'       => 'security@iiitkota.ac.in',
            'office'      => 'Main Security Gate',
        ],
    ],
];

