<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the dashboard routes prefix and middleware.
    |
    */
    'routes' => [
        'prefix' => env('TYRO_DASHBOARD_PREFIX', 'dashboard'),
        'middleware' => ['web', 'auth'],
        'name_prefix' => 'tyro-dashboard.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Roles
    |--------------------------------------------------------------------------
    |
    | Users with these roles will have full access to admin features
    | (user management, role management, privilege management, settings).
    |
    */
    'admin_roles' => ['admin', 'super-admin'],

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The user model to use throughout the dashboard.
    |
    */
    'user_model' => env('TYRO_DASHBOARD_USER_MODEL', 'App\\Models\\User'),

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    |
    | Default pagination settings for lists.
    |
    */
    'pagination' => [
        'users' => 15,
        'roles' => 15,
        'privileges' => 15,
    ],

    /*
    |--------------------------------------------------------------------------
    | Branding
    |--------------------------------------------------------------------------
    |
    | Customize the dashboard appearance.
    |
    */
    'branding' => [
        'app_name' => env('TYRO_DASHBOARD_APP_NAME', env('APP_NAME', 'Laravel')),
        'logo' => env('TYRO_DASHBOARD_LOGO', null),
        'logo_height' => env('TYRO_DASHBOARD_LOGO_HEIGHT', '32px'),
        'favicon' => env('TYRO_DASHBOARD_FAVICON', null),

        // Sidebar colors (supports any CSS color value: hex, rgb, hsl, etc.)
        'sidebar_bg' => env('TYRO_DASHBOARD_SIDEBAR_BG', null), // Custom background color for sidebar
        'sidebar_text' => env('TYRO_DASHBOARD_SIDEBAR_TEXT', null), // Custom text color for sidebar
        'sidebar_primary' => env('TYRO_DASHBOARD_SIDEBAR_PRIMARY', null), // Custom text color for sidebar
        'sidebar_accent' => env('TYRO_DASHBOARD_SIDEBAR_ACCENT', null), // Custom text color for sidebar
        'sidebar_accent_foreground' => env('TYRO_DASHBOARD_SIDEBAR_ACCENT_FOREGROUND', null), // Custom text color for sidebar
        'sidebar_header_border' => env('TYRO_DASHBOARD_SIDEBAR_HEADER_BORDER', null), // Custom text color for sidebar
        'sidebar_accordion_compact' => filter_var(env('TYRO_DASHBOARD_SIDEBAR_ACCORDION_COMPACT', false), FILTER_VALIDATE_BOOLEAN),
        'sidebar_accordion_open_sections' => (int) env('TYRO_DASHBOARD_SIDEBAR_ACCORDION_OPEN_SECTIONS', 1),
        'sidebar_logo' => env('TYRO_DASHBOARD_SIDEBAR_LOGO', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Bar
    |--------------------------------------------------------------------------
    |
    | Configuration for the admin notice bar displayed at the top of the dashboard.
    |
    */
    'admin_bar' => [
        'enabled' => env('TYRO_DASHBOARD_ADMIN_BAR_ENABLED', false),
        'message' => env('TYRO_DASHBOARD_ADMIN_BAR_MESSAGE', ''),
        'bg_color' => env('TYRO_DASHBOARD_ADMIN_BAR_BG_COLOR', '#000000'),
        'text_color' => env('TYRO_DASHBOARD_ADMIN_BAR_TEXT_COLOR', '#ffffff'),
        'align' => env('TYRO_DASHBOARD_ADMIN_BAR_ALIGN', 'left'),
        'height' => env('TYRO_DASHBOARD_ADMIN_BAR_HEIGHT', '40px'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Collapsible Sidebar
    |--------------------------------------------------------------------------
    |
    | Enable or disable the collapsible sidebar feature.
    |
    */
    'collapsible_sidebar' => env('TYRO_DASHBOARD_COLLAPSIBLE_SIDEBAR', true),

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific dashboard features.
    |
    */
    'features' => [
        'user_management' => true,
        'role_management' => true,
        'privilege_management' => true,
        'settings_management' => true,
        'profile_management' => true,
        'invitation_system' => env('TYRO_DASHBOARD_ENABLE_INVITATION', true),
        'audit_logs' => env('TYRO_DASHBOARD_ENABLE_AUDIT_LOGS', true),
        'system_settings' => env('TYRO_DASHBOARD_ENABLE_SYSTEM_SETTINGS', true),
        'show_roles_menu' => env('TYRO_DASHBOARD_SHOW_ROLES_MENU', true),
        'show_privileges_menu' => env('TYRO_DASHBOARD_SHOW_PRIVILEGES_MENU', true),
        'show_resources_menu' => env('TYRO_DASHBOARD_SHOW_RESOURCES_MENU', true),
        'activity_log' => false, // Future feature
        'profile_photo_upload' => env('TYRO_DASHBOARD_ENABLE_PROFILE_PHOTO', false),
        'gravatar' => env('TYRO_DASHBOARD_ENABLE_GRAVATAR', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Protected Resources
    |--------------------------------------------------------------------------
    |
    | Resources that cannot be deleted through the dashboard.
    |
    */
    'protected' => [
        'roles' => ['admin', 'super-admin', 'user'],
        'users' => [], // Add user IDs that cannot be deleted
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard Widgets
    |--------------------------------------------------------------------------
    |
    | Configure which widgets appear on the dashboard home.
    |
    */
    'widgets' => [
        'stats' => true,
        'recent_users' => true,
        'role_distribution' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | Configure dashboard notifications behavior.
    |
    */
    'notifications' => [
        'show_flash_messages' => true,
        'auto_dismiss_seconds' => 5,
        'notification_style' => env('TYRO_DASHBOARD_NOTIFICATION_STYLE', 'legacy'), // 'legacy' or 'toast'
        'toast_position' => env('TYRO_DASHBOARD_TOAST_POSITION', 'bottom-right'), // 'top-right' or 'bottom-right'
    ],

    /*
    |--------------------------------------------------------------------------
    | File Upload Configuration
    |--------------------------------------------------------------------------
    |
    | Configure default settings for file uploads in resources.
    |
    */
    'uploads' => [
        'disk' => env('TYRO_DASHBOARD_UPLOAD_DISK', 'public'),
        'directory' => env('TYRO_DASHBOARD_UPLOAD_DIRECTORY', 'uploads'),
        'auto_delete_on_resource_delete' => env('TYRO_DASHBOARD_AUTO_DELETE_UPLOADS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Profile Photo Configuration
    |--------------------------------------------------------------------------
    |
    | Configure settings for user profile photos and gravatar support.
    |
    */
    'profile_photo' => [
        'disk' => env('TYRO_DASHBOARD_PROFILE_PHOTO_DISK', 'public'),
        'directory' => env('TYRO_DASHBOARD_PROFILE_PHOTO_DIRECTORY', 'profile_images'),
        'max_size' => env('TYRO_DASHBOARD_PROFILE_PHOTO_MAX_SIZE', 10240), // in KB (default 10MB)
        'width' => env('TYRO_DASHBOARD_PROFILE_PHOTO_WIDTH', 400),
        'height' => env('TYRO_DASHBOARD_PROFILE_PHOTO_HEIGHT', 400),
        'quality' => env('TYRO_DASHBOARD_PROFILE_PHOTO_QUALITY', 90),
        'crop_position' => env('TYRO_DASHBOARD_PROFILE_PHOTO_CROP', 'center'), // top, center, bottom
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'auto_delete_on_user_delete' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Dynamic Resources (CRUD)
    |--------------------------------------------------------------------------
    |
    | Define your resources here to automatically generate CRUD interfaces.
    |
    */
    // 'resources' => [
    //     // Example:
    //     // 'posts' => [
    //     //     'model' => 'App\Models\Post',
    //     //     'title' => 'Posts',
    //     //     'icon' => '<svg>...</svg>', // Optional SVG icon
    //     //     'fields' => [
    //     //         'title' => ['type' => 'text', 'label' => 'Title', 'rules' => 'required'],
    //     //         'content' => ['type' => 'textarea', 'label' => 'Content'],
    //     //     ],
    //     // ],
    // ],
    'resources' => [
        'companies' => [
            'model' => 'App\Models\Company',
            'title' => 'Companies',
            'fields' => [
                'company_code' => ['type' => 'text', 'label' => 'Company Code', 'rules' => 'required|string|max:255|unique:companies,company_code'],
                'company_name' => ['type' => 'text', 'label' => 'Company Name', 'rules' => 'required|string|max:255'],
                'address' => ['type' => 'textarea', 'label' => 'Address'],
                'city' => ['type' => 'text', 'label' => 'City'],
                'country' => ['type' => 'text', 'label' => 'Country'],
                'status' => ['type' => 'select', 'label' => 'Status', 'options' => ['active' => 'Active', 'inactive' => 'Inactive']],
            ],
        ],
        'obligation_types' => [
            'model' => 'App\Models\ObligationType',
            'title' => 'Obligation Types',
            'fields' => [
                'type_name' => ['type' => 'text', 'label' => 'Type Name', 'rules' => 'required|string|max:255|unique:obligation_types,type_name'],
                'description' => ['type' => 'textarea', 'label' => 'Description'],
                'default_priority' => ['type' => 'select', 'label' => 'Default Priority', 'options' => ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical']],
                'default_risk_level' => ['type' => 'select', 'label' => 'Default Risk Level', 'options' => ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical']],
                'approval_required' => ['type' => 'boolean', 'label' => 'Approval Required'],
                'renewal_required' => ['type' => 'boolean', 'label' => 'Renewal Required'],
                'active' => ['type' => 'boolean', 'label' => 'Active'],
            ],
        ],
        'obligation_categories' => [
            'model' => 'App\Models\ObligationCategory',
            'title' => 'Obligation Categories',
            'fields' => [
                'category_name' => ['type' => 'text', 'label' => 'Category Name', 'rules' => 'required|string|max:255|unique:obligation_categories,category_name'],
                'description' => ['type' => 'textarea', 'label' => 'Description'],
                'active' => ['type' => 'boolean', 'label' => 'Active'],
            ],
        ],
        'notification_rules' => [
            'model' => 'App\Models\NotificationRule',
            'title' => 'Notification Rules',
            'fields' => [
                'days_before_expiry' => ['type' => 'number', 'label' => 'Days Before Expiry', 'rules' => 'required|integer|min:0'],
                'notification_level' => ['type' => 'text', 'label' => 'Notification Level', 'rules' => 'required|string|max:255'],
                'recipient_type' => ['type' => 'select', 'label' => 'Recipient Type', 'options' => ['OWNER' => 'Owner', 'BACKUP_OWNER' => 'Backup Owner', 'MANAGER' => 'Manager', 'DEPARTMENT_HEAD' => 'Department Head', 'APPROVER' => 'Approver', 'MANAGEMENT' => 'Management', 'SPECIFIC_USER' => 'Specific User']],
                'channel' => ['type' => 'select', 'label' => 'Channel', 'options' => ['IN_APP' => 'In-App', 'EMAIL' => 'Email']],
                'subject_template' => ['type' => 'text', 'label' => 'Subject Template'],
                'message_template' => ['type' => 'textarea', 'label' => 'Message Template'],
                'active' => ['type' => 'boolean', 'label' => 'Active'],
            ],
        ],
        'escalation_rules' => [
            'model' => 'App\Models\EscalationRule',
            'title' => 'Escalation Rules',
            'fields' => [
                'days_before_expiry' => ['type' => 'number', 'label' => 'Days Before Expiry', 'rules' => 'nullable|integer|min:0'],
                'days_after_expiry' => ['type' => 'number', 'label' => 'Days After Expiry', 'rules' => 'nullable|integer|min:0'],
                'escalation_level' => ['type' => 'text', 'label' => 'Escalation Level', 'rules' => 'required|string|max:255'],
                'recipient_type' => ['type' => 'select', 'label' => 'Recipient Type', 'options' => ['OWNER' => 'Owner', 'BACKUP_OWNER' => 'Backup Owner', 'MANAGER' => 'Manager', 'DEPARTMENT_HEAD' => 'Department Head', 'SPECIFIC_USER' => 'Specific User']],
                'channel' => ['type' => 'select', 'label' => 'Channel', 'options' => ['IN_APP' => 'In-App', 'EMAIL' => 'Email']],
                'active' => ['type' => 'boolean', 'label' => 'Active'],
            ],
        ],
        'approval_workflows' => [
            'model' => 'App\Models\ApprovalWorkflow',
            'title' => 'Approval Workflows',
            'fields' => [
                'name' => ['type' => 'text', 'label' => 'Workflow Name', 'rules' => 'required|string|max:255'],
                'description' => ['type' => 'textarea', 'label' => 'Description'],
                'active' => ['type' => 'boolean', 'label' => 'Active'],
            ],
        ],
        'approval_workflow_steps' => [
            'model' => 'App\Models\ApprovalWorkflowStep',
            'title' => 'Workflow Steps',
            'fields' => [
                'approval_workflow_id' => ['type' => 'select', 'label' => 'Workflow', 'relationship' => 'workflow', 'option_label' => 'name'],
                'step_order' => ['type' => 'number', 'label' => 'Step Order', 'rules' => 'required|integer|min:1'],
                'approver_type' => ['type' => 'text', 'label' => 'Approver Type', 'rules' => 'required|string|max:255'],
                'approver_user_id' => ['type' => 'select', 'label' => 'Approver', 'relationship' => 'approver', 'option_label' => 'name'],
                'required' => ['type' => 'boolean', 'label' => 'Required'],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resource UI Settings
    |--------------------------------------------------------------------------
    |
    | Configure the appearance and behavior of resource forms and lists.
    |
    */
    'resource_ui' => [
        'show_global_errors' => env('TYRO_SHOW_GLOBAL_ERRORS', true),
        'show_field_errors' => env('TYRO_SHOW_FIELD_ERRORS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Disable Examples
    |--------------------------------------------------------------------------
    |
    | If this is true, the "Examples" section in the sidebar will be hidden
    | and the example routes will be disabled.
    |
    */
    'disable_examples' => env('TYRO_DASHBOARD_DISABLE_EXAMPLES', false),

    /*
    |--------------------------------------------------------------------------
    | Media
    |--------------------------------------------------------------------------
    |
    | Configure media library API keys for external image import providers.
    |
    */
    'media' => [
        'max_size' => env('TYRO_DASHBOARD_MEDIA_MAX_SIZE', 10240),
        'api_keys' => [
            'freepik' => env('TYRO_DASHBOARD_FREEPIK_KEY'),
            'pexels' => env('TYRO_DASHBOARD_PEXELS_KEY'),
            'unsplash' => env('TYRO_DASHBOARD_UNSPLASH_ACCESS_KEY'),
            'pixabay' => env('TYRO_DASHBOARD_PIXABAY_KEY'),
        ],
    ],
];
