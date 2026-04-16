<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CAAC Rubric — Phase 1 (Campus-level Alumni Award Committee)
    |--------------------------------------------------------------------------
    */

    'caac' => [

        'impact' => [
            'label'  => 'Impact',
            'weight' => 40,
            'total'  => 40,
            'items'  => [
                [
                    'key'   => 'career_achievements',
                    'label' => 'Career Achievements',
                    'desc'  => 'Positions held, promotions, recognitions',
                    'max'   => 10,
                    'order' => 1,
                    // Excel column: "Career Score" / "Career Brief"
                ],
                [
                    'key'   => 'measurable_outcomes',
                    'label' => 'Measurable Outcomes',
                    'desc'  => 'KPIs, revenue, research outputs, results',
                    'max'   => 10,
                    'order' => 2,
                    // Excel column: "Outcomes Score" / "Outcomes Brief"
                ],
                [
                    'key'   => 'societal_contribution',
                    'label' => 'Societal Contribution',
                    'desc'  => 'Community benefit, national/global relevance',
                    'max'   => 10,
                    'order' => 3,
                    // Excel column: "Societal Score" / "Societal Brief"
                ],
                [
                    'key'   => 'contribution_through_projects',
                    'label' => 'Contribution Through Projects',
                    'desc'  => 'Projects led, jobs created, policies influenced',
                    'max'   => 10,
                    'order' => 4,
                    // Excel column: "Projects Score" / "Projects Brief"
                ],
            ],
        ],

        'leadership_service' => [
            'label'  => 'Leadership & Service',
            'weight' => 25,
            'total'  => 25,
            'items'  => [
                [
                    'key'   => 'leadership_roles',
                    'label' => 'Leadership Roles',
                    'desc'  => 'Positions of influence, team/project leadership',
                    'max'   => 8,
                    'order' => 5,
                    // Excel column: "Leadership Score" / "Leadership Brief"
                ],
                [
                    'key'   => 'mentoring_capacity_building',
                    'label' => 'Mentoring & Capacity Building',
                    'desc'  => 'Mentoring juniors, training, guiding teams',
                    'max'   => 6,
                    'order' => 6,
                    // Excel column: "Mentoring Score" / "Mentoring Brief"
                ],
                [
                    'key'   => 'service_institution_community',
                    'label' => 'Service to Institution/Community',
                    'desc'  => 'Voluntary service, CUI contributions',
                    'max'   => 6,
                    'order' => 7,
                    // Excel column: "Service Score" / "Service Brief"
                ],
                [
                    'key'   => 'inspire_role_model',
                    'label' => 'Ability to Inspire & Role Model',
                    'desc'  => 'Integrity, motivation, positive influence',
                    'max'   => 5,
                    'order' => 8,
                    // Excel column: "Inspiration Score" / "Inspiration Brief"
                ],
            ],
        ],

        'innovation_creativity' => [
            'label'  => 'Innovation & Creativity',
            'weight' => 20,
            'total'  => 20,
            'items'  => [
                [
                    'key'   => 'entrepreneurship_startup',
                    'label' => 'Entrepreneurship / Start-up Leadership',
                    'desc'  => 'Founding ventures, commercialization',
                    'max'   => 6,
                    'order' => 9,
                    // Excel column: "Entrepreneur Score" / "Entrepreneur Brief"
                ],
                [
                    'key'   => 'research_knowledge',
                    'label' => 'Research Output & Knowledge Creation',
                    'desc'  => 'Publications, citations, grants',
                    'max'   => 5,
                    'order' => 10,
                    // Excel column: "Research Score" / "Research Brief"
                ],
                [
                    'key'   => 'patents_products',
                    'label' => 'Patents, Products, or Technological Innovations',
                    'desc'  => 'Patents, prototypes',
                    'max'   => 5,
                    'order' => 11,
                    // Excel column: "Patent Score" / "Patent Brief"
                ],
                [
                    'key'   => 'creativity_problem_solving',
                    'label' => 'Creativity in Problem-Solving',
                    'desc'  => 'Original solutions, unique methodologies',
                    'max'   => 4,
                    'order' => 12,
                    // Excel column: "Creativity Score" / "Creativity Brief"
                ],
            ],
        ],

        'ethics_engagement' => [
            'label'  => 'Ethics & Engagement',
            'weight' => 15,
            'total'  => 15,
            'items'  => [
                [
                    'key'   => 'integrity_ethics',
                    'label' => 'Integrity & Ethical Conduct',
                    'desc'  => 'Professional conduct, honesty',
                    'max'   => 5,
                    'order' => 13,
                    // Excel column: "Integrity Score" / "Integrity Brief"
                ],
                [
                    'key'   => 'alumni_contribution',
                    'label' => 'Contribution to Alumni Community / Networks',
                    'desc'  => 'Alumni chapters, mentoring',
                    'max'   => 4,
                    'order' => 14,
                    // Excel column: "Alumni Score" / "Alumni Brief"
                ],
                [
                    'key'   => 'community_engagement',
                    'label' => 'Community Engagement / Social Responsibility',
                    'desc'  => 'Volunteerism, philanthropy',
                    'max'   => 4,
                    'order' => 15,
                    // Excel column: "Community Score" / "Community Brief"
                ],
                [
                    'key'   => 'professional_reputation',
                    'label' => 'Professional Reputation / Peer Recognition',
                    'desc'  => 'Testimonials, awards',
                    'max'   => 2,
                    'order' => 16,
                    // Excel column: "Reputation Score" / "Reputation Brief"
                ],
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Excel Column → Rubric Item Mapping (for ImportController + Seeder)
    |--------------------------------------------------------------------------
    |
    | "pa" = Professional Achievement (name at col index 2, scores start at 16)
    | "std" = Standard (all other files, name at col index 1, scores start at 14)
    |
    | Each entry: [score_col_index, brief_col_index, sub_indicator_key]
    |
    */

    'excel_score_columns' => [
        [0,  1,  'career_achievements'],          // Career Score / Career Brief
        [2,  3,  'measurable_outcomes'],           // Outcomes Score / Outcomes Brief
        [4,  5,  'societal_contribution'],         // Societal Score / Societal Brief
        [6,  7,  'contribution_through_projects'], // Projects Score / Projects Brief
        [8,  9,  'leadership_roles'],              // Leadership Score / Leadership Brief
        [10, 11, 'mentoring_capacity_building'],   // Mentoring Score / Mentoring Brief
        [12, 13, 'service_institution_community'], // Service Score / Service Brief
        [14, 15, 'inspire_role_model'],            // Inspiration Score / Inspiration Brief
        [16, 17, 'entrepreneurship_startup'],      // Entrepreneur Score / Entrepreneur Brief
        [18, 19, 'research_knowledge'],            // Research Score / Research Brief
        [20, 21, 'patents_products'],              // Patent Score / Patent Brief
        [22, 23, 'creativity_problem_solving'],    // Creativity Score / Creativity Brief
        [24, 25, 'integrity_ethics'],              // Integrity Score / Integrity Brief
        [26, 27, 'alumni_contribution'],           // Alumni Score / Alumni Brief
        [28, 29, 'community_engagement'],          // Community Score / Community Brief
        [30, 31, 'professional_reputation'],       // Reputation Score / Reputation Brief
    ],

    /*
    |--------------------------------------------------------------------------
    | Excel File → Category Mapping
    |--------------------------------------------------------------------------
    */

    'excel_files' => [
        [
            'file'        => 'Professional Achievement program wise.xlsx',
            'sheet'       => 'All Departments1',
            'category'    => 'professional-achievement',
            'name_col'    => 2,  // 0-indexed: col 3
            'score_start' => 16, // 0-indexed: col 17
            'email_col'   => 12,
            'phone_col'   => 13,
            'dept_col'    => 5,
            'campus_col'  => 6,
            'batch_col'   => 4,
            'extra_info_cols' => [8, 10, 11, 14], // DegreeProgram, ProfTitle, Org, LinkedIn
        ],
        [
            'file'        => 'Distinguished Young Alumni program wise.xlsx',
            'sheet'       => 'All Departments',
            'category'    => 'distinguished-young-alumni',
            'name_col'    => 1,
            'score_start' => 14,
            'email_col'   => 10,
            'phone_col'   => 11,
            'dept_col'    => 4,
            'campus_col'  => 5,
            'batch_col'   => 3,
            'extra_info_cols' => [7, 8, 9, 12],
        ],
        [
            'file'        => 'Innovation &amp; Entrepreneursh.xlsx',
            'sheet'       => 'All Departments',
            'category'    => 'innovation-entrepreneurship',
            'name_col'    => 1,
            'score_start' => 14,
            'email_col'   => 10,
            'phone_col'   => 11,
            'dept_col'    => 4,
            'campus_col'  => 5,
            'batch_col'   => 3,
            'extra_info_cols' => [7, 8, 9, 12],
        ],
        [
            'file'        => 'Social Impact &amp; Community Service.xlsx',
            'sheet'       => 'All Departments',
            'category'    => 'social-impact-community',
            'name_col'    => 1,
            'score_start' => 14,
            'email_col'   => 10,
            'phone_col'   => 11,
            'dept_col'    => 4,
            'campus_col'  => 5,
            'batch_col'   => 3,
            'extra_info_cols' => [7, 8, 9, 12],
        ],
    ],

];
