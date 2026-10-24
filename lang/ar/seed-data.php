<?php

/*
 * Arabic names for the reference data the installers seed in English.
 * Shape: 'table.column' => ['English seed value' => 'Arabic name'].
 * Applied by `php artisan erp:localize-data` to rows that still hold the seeded value.
 */

return [

    'accounts_accounts.name' => [
        'Current Assets'               => 'الأصول المتداولة',
        'Stock Valuation'              => 'تقييم المخزون',
        'Stock Interim (Received)'     => 'مخزون مؤقت (مستلم)',
        'Stock Interim (Delivered)'    => 'مخزون مؤقت (مسلَّم)',
        'Cost of Production'           => 'تكلفة الإنتاج',
        'Work in Progress'             => 'أعمال قيد التنفيذ',
        'Account Receivable'           => 'ذمم مدينة',
        'Products to receive'          => 'منتجات مستحقة الاستلام',
        'Prepaid Expenses'             => 'مصروفات مدفوعة مقدماً',
        'Tax Paid'                     => 'ضريبة مدفوعة',
        'Tax Receivable'               => 'ضريبة مستحقة القبض',
        'Prepayments'                  => 'دفعات مقدمة',
        'Fixed Asset'                  => 'أصول ثابتة',
        'Non-current assets'           => 'الأصول غير المتداولة',
        'Current Liabilities'          => 'الخصوم المتداولة',
        'Account Payable'              => 'ذمم دائنة',
        'Bills to receive'             => 'فواتير مستحقة الاستلام',
        'Deferred Revenue'             => 'إيرادات مؤجلة',
        'Salary Payable'               => 'رواتب مستحقة الدفع',
        'Employee Payroll Taxes'       => 'ضرائب رواتب الموظفين',
        'Employer Payroll Taxes'       => 'ضرائب رواتب صاحب العمل',
        'Tax Received'                 => 'ضريبة محصّلة',
        'Tax Payable'                  => 'ضريبة مستحقة الدفع',
        'Non-current Liabilities'      => 'الخصوم غير المتداولة',
        'Capital'                      => 'رأس المال',
        'Dividends'                    => 'توزيعات الأرباح',
        'Product Sales'                => 'مبيعات المنتجات',
        'Foreign Exchange Gain'        => 'أرباح فروق العملة',
        'Cash Difference Gain'         => 'أرباح فروق النقد',
        'Cash Discount Loss'           => 'خسائر خصم السداد',
        'Other Income'                 => 'إيرادات أخرى',
        'Cost of Goods Sold'           => 'تكلفة البضاعة المباعة',
        'Expenses'                     => 'المصروفات',
        'Purchase of Equipments'       => 'شراء معدات',
        'Rent'                         => 'الإيجار',
        'Bank Fees'                    => 'رسوم بنكية',
        'Salary Expenses'              => 'مصروف الرواتب',
        'Foreign Exchange Loss'        => 'خسائر فروق العملة',
        'Cash Difference Loss'         => 'خسائر فروق النقد',
        'Cash Discount Gain'           => 'أرباح خصم السداد',
        'RD Expenses'                  => 'مصروفات البحث والتطوير',
        'Sales Expenses'               => 'مصروفات المبيعات',
        'Account Receivable (PoS)'     => 'ذمم مدينة (نقاط البيع)',
        'Credit Card'                  => 'بطاقة ائتمان',
        'Bank'                         => 'البنك',
        'Cash'                         => 'النقدية',
        'Bank Suspense Account'        => 'حساب معلّق للبنك',
        'Liquidity Transfer'           => 'تحويل سيولة',
        'Outstanding Receipts'         => 'مقبوضات معلّقة',
        'Outstanding Payments'         => 'مدفوعات معلّقة',
        'Undistributed Profits/Losses' => 'أرباح/خسائر غير موزعة',
    ],

    'accounts_account_tags.name' => [
        'Operating Activities'                 => 'الأنشطة التشغيلية',
        'Financing Activities'                 => 'الأنشطة التمويلية',
        'Investing & Extraordinary Activities' => 'الأنشطة الاستثمارية والاستثنائية',
        'Office Furniture'                     => 'أثاث مكتبي',
    ],

    'accounts_incoterms.name' => [
        'EX WORKS'                       => 'تسليم أرض المصنع (EXW)',
        'FREE CARRIER'                   => 'تسليم الناقل (FCA)',
        'FREE ALONGSIDE SHIP'            => 'تسليم بجانب السفينة (FAS)',
        'FREE ON BOARD'                  => 'تسليم ظهر السفينة (FOB)',
        'COST AND FREIGHT'               => 'التكلفة والشحن (CFR)',
        'COST, INSURANCE AND FREIGHT'    => 'التكلفة والتأمين والشحن (CIF)',
        'CARRIAGE PAID TO'               => 'أجرة النقل مدفوعة إلى (CPT)',
        'CARRIAGE AND INSURANCE PAID TO' => 'أجرة النقل والتأمين مدفوعة إلى (CIP)',
        'DELIVERED AT PLACE UNLOADED'    => 'تسليم في المكان بعد التفريغ (DPU)',
        'DELIVERED AT PLACE'             => 'تسليم في المكان (DAP)',
        'DELIVERED DUTY PAID'            => 'تسليم مع دفع الرسوم (DDP)',
    ],

    'accounts_journals.name' => [
        'Customer Invoices'        => 'فواتير العملاء',
        'Vendor Bills'             => 'فواتير الموردين',
        'Miscellaneous Operations' => 'عمليات متنوعة',
        'Exchange Difference'      => 'فروق العملة',
        'Bank Transactions'        => 'معاملات البنك',
        'Cash Transactions'        => 'معاملات نقدية',
    ],

    'accounts_payment_methods.name'      => ['Manual Payment' => 'دفع يدوي'],
    'accounts_payment_method_lines.name' => ['Manual Payment' => 'دفع يدوي'],

    'accounts_payment_terms.name' => [
        'Immediate Payment'                 => 'دفع فوري',
        '15 Days'                           => '15 يوماً',
        '21 Days'                           => '21 يوماً',
        '30 Days'                           => '30 يوماً',
        '45 Days'                           => '45 يوماً',
        'End of Following Month'            => 'نهاية الشهر التالي',
        '10 Days after End of Next Month'   => '10 أيام بعد نهاية الشهر التالي',
        '30% Now, Balance 60 Days'          => '30% الآن، والباقي بعد 60 يوماً',
        '2/7 Net 30'                        => 'خصم 2% خلال 7 أيام، الصافي 30 يوماً',
        '90 Days, on the 10th'              => '90 يوماً، في اليوم العاشر',
    ],

    'approval_flows.name' => ['Purchase Order Approval Flow' => 'مسار موافقة أوامر الشراء'],

    'approval_flows.description' => ['3-step sequential approval flow for purchase orders.' => 'مسار موافقة متسلسل من ثلاث خطوات لأوامر الشراء.'],

    'approval_steps.name' => [
        'Department Manager Review' => 'مراجعة مدير القسم',
        'Finance Manager Review'    => 'مراجعة المدير المالي',
        'General Manager Review'    => 'مراجعة المدير العام',
    ],

    'activity_plans.name' => ['Offboarding' => 'إنهاء الخدمة', 'Onboarding' => 'مباشرة العمل'],

    'activity_types.name' => ['Meeting' => 'اجتماع', 'Exception' => 'استثناء', 'To-Do' => 'مهمة', 'Call' => 'مكالمة'],

    'activity_types.summary' => ['Meeting' => 'اجتماع', 'Exception' => 'استثناء', 'To-Do' => 'مهمة', 'Call' => 'مكالمة'],

    'employees_departments.name' => [
        'Administration'         => 'الإدارة',
        'Long Term Projects'     => 'المشاريع طويلة الأجل',
        'Management'             => 'الإدارة العليا',
        'Professional Services'  => 'الخدمات المهنية',
        'R&D USA'                => 'البحث والتطوير',
        'Research & Development' => 'البحث والتطوير',
        'Sales'                  => 'المبيعات',
        'Finance'                => 'المالية',
        'Human Resources'        => 'الموارد البشرية',
        'Operations'             => 'العمليات',
    ],

    'employees_categories.name' => ['Consultant' => 'مستشار', 'Employee' => 'موظف', 'Sales' => 'مبيعات', 'Trainer' => 'مدرّب'],

    'employees_departure_reasons.name' => ['Fired' => 'إنهاء خدمة', 'Resigned' => 'استقالة', 'Retired' => 'تقاعد'],

    'employees_employment_types.name' => [
        'Permanent'      => 'دائم',
        'Temporary'      => 'مؤقت',
        'Seasonal'       => 'موسمي',
        'Interim'        => 'انتقالي',
        'Full-Time'      => 'دوام كامل',
        'Intern'         => 'متدرب',
        'Student'        => 'طالب',
        'Apprenticeship' => 'تدريب مهني',
        'Thesis'         => 'رسالة علمية',
        'Statutory'      => 'نظامي',
        'Employee'       => 'موظف',
    ],

    'employees_job_positions.name' => [
        'Software Engineer'           => 'مهندس برمجيات',
        'HR Manager'                  => 'مدير الموارد البشرية',
        'Marketing Specialist'        => 'أخصائي تسويق',
        'Sales Manager'               => 'مدير المبيعات',
        'Product Manager'             => 'مدير المنتجات',
        'UX/UI Designer'              => 'مصمم واجهات وتجربة المستخدم',
        'Customer Support Specialist' => 'أخصائي دعم العملاء',
        'Data Scientist'              => 'عالم بيانات',
        'Finance Analyst'             => 'محلل مالي',
        'Legal Advisor'               => 'مستشار قانوني',
    ],

    'employees_skill_types.name' => [
        'Languages' => 'اللغات', 'Soft Skills' => 'المهارات الشخصية', 'Programming Languages' => 'لغات البرمجة', 'IT' => 'تقنية المعلومات', 'Marketing' => 'التسويق',
    ],

    'employees_skill_levels.name' => [
        'Beginner' => 'مبتدئ', 'Elementary' => 'أساسي', 'Intermediate' => 'متوسط', 'Advanced' => 'متقدم', 'Expert' => 'خبير',
    ],

    'employees_skills.name' => [
        'French'                                 => 'الفرنسية', 'Spanish' => 'الإسبانية', 'English' => 'الإنجليزية', 'German' => 'الألمانية', 'Filipino' => 'الفلبينية',
        'Arabic'                                 => 'العربية', 'Bengali' => 'البنغالية', 'Mandarin Chinese' => 'الصينية (ماندرين)', 'Wu Chinese' => 'الصينية (وو)',
        'Hindi'                                  => 'الهندية', 'Russian' => 'الروسية', 'Portuguese' => 'البرتغالية', 'Indonesian' => 'الإندونيسية', 'Urdu' => 'الأردية',
        'Japanese'                               => 'اليابانية', 'Punjabi' => 'البنجابية', 'Javanese' => 'الجاوية', 'Telugu' => 'التيلوغوية', 'Turkish' => 'التركية',
        'Korean'                                 => 'الكورية', 'Marathi' => 'الماراثية',
        'Communication'                          => 'التواصل', 'Teamwork' => 'العمل الجماعي', 'Problem-Solving' => 'حل المشكلات', 'Time Management' => 'إدارة الوقت',
        'Critical Thinking'                      => 'التفكير النقدي', 'Decision-Making' => 'اتخاذ القرار', 'Organizational' => 'التنظيم',
        'Stress management'                      => 'إدارة الضغوط', 'Adaptability' => 'القدرة على التكيف', 'Conflict Management' => 'إدارة النزاعات',
        'Leadership'                             => 'القيادة', 'Creativity' => 'الإبداع', 'Resourcefulness' => 'سعة الحيلة', 'Persuasion' => 'الإقناع',
        'Openness to criticism'                  => 'تقبّل النقد',
        'Web Development'                        => 'تطوير الويب', 'Database Management' => 'إدارة قواعد البيانات', 'Cloud computing' => 'الحوسبة السحابية',
        'Network administration'                 => 'إدارة الشبكات', 'Cybersecurity' => 'الأمن السيبراني', 'Machine Learning (AI)' => 'تعلّم الآلة (الذكاء الاصطناعي)',
        'Data analysis/visualization'            => 'تحليل البيانات وعرضها', 'Agile and Scrum methodologies' => 'منهجيات أجايل وسكرام',
        'Mobile app development'                 => 'تطوير تطبيقات الجوال', 'Project Management' => 'إدارة المشاريع',
        'System Administration (Linux, Windows)' => 'إدارة الأنظمة (لينكس، ويندوز)', 'Virtualization and Containerization' => 'المحاكاة الافتراضية والحاويات',
        'IT support'                             => 'الدعم الفني', 'IT infrastructure and architecture' => 'البنية التحتية والمعمارية التقنية',
        'IT service management (ITSM)'           => 'إدارة خدمات تقنية المعلومات', 'Big data technologies (Hadoop, Spark)' => 'تقنيات البيانات الضخمة',
        'IoT and embedded systems'               => 'إنترنت الأشياء والأنظمة المدمجة', 'IT governance and compliance (GDPR, HIPAA,...)' => 'حوكمة تقنية المعلومات والامتثال',
        'Analytics'                              => 'التحليلات', 'Digital advertising' => 'الإعلان الرقمي', 'Public Speaking' => 'التحدث أمام الجمهور', 'Email Marketing' => 'التسويق عبر البريد الإلكتروني',
    ],

    'employees_work_locations.name' => ['Home' => 'المنزل', 'Building 1, Second Floor' => 'المبنى الرئيسي، الطابق الثاني', 'Other' => 'أخرى'],

    'calendars.name' => ['Standard 40 hours/week' => 'قياسي 40 ساعة أسبوعيًا'],

    'inventories_locations.name' => [
        'Physical Locations'   => 'المواقع الفعلية', 'Partners' => 'الشركاء', 'Virtual Locations' => 'المواقع الافتراضية', 'Vendors' => 'الموردون',
        'Customers'            => 'العملاء', 'Inter Company Transit' => 'عبور بين الشركات', 'Inter Warehouse Transit' => 'عبور بين المستودعات',
        'Inventory Adjustment' => 'تسوية المخزون', 'Production' => 'الإنتاج', 'Scrap' => 'الهالك', 'WH' => 'المستودع', 'Stock' => 'المخزون',
        'Input'                => 'الاستلام', 'Quality Control' => 'مراقبة الجودة', 'Output' => 'الشحن', 'Packing Zone' => 'منطقة التعبئة',
        'Pre-Production'       => 'قبل الإنتاج', 'Post-Production' => 'بعد الإنتاج',
    ],

    'inventories_operation_types.name' => [
        'Receipts'        => 'الاستلام', 'Delivery Orders' => 'أوامر التسليم', 'Pick' => 'الالتقاط', 'Pack' => 'التعبئة', 'Quality Control' => 'مراقبة الجودة',
        'Storage'         => 'التخزين', 'Internal Transfers' => 'التحويلات الداخلية', 'Cross Dock' => 'العبور المباشر', 'Dropship' => 'الشحن المباشر',
        'Pick Components' => 'التقاط المكونات', 'Store Finished Product' => 'تخزين المنتج النهائي', 'Manufacturing' => 'التصنيع',
    ],

    'inventories_routes.name' => [
        'Replenish on Order (MTO)'                  => 'التزويد عند الطلب (MTO)',
        'Your Company: Receive in 1 step (Stock)'   => 'الاستلام في خطوة واحدة (المخزون)',
        'Your Company: Deliver in 1 step (Ship)'    => 'التسليم في خطوة واحدة (الشحن)',
        'Your Company: Cross-Dock'                  => 'العبور المباشر',
        'Buy'                                       => 'شراء',
        'Dropship'                                  => 'الشحن المباشر',
        'Your Company: Manufacture (1 step)'        => 'التصنيع (خطوة واحدة)',
    ],

    'inventories_rules.name' => [
        'WH: Vendors → Stock'               => 'المستودع: الموردون ← المخزون',
        'WH: Stock → Customers'             => 'المستودع: المخزون ← العملاء',
        'WH: Vendors → Customers'           => 'المستودع: الموردون ← العملاء',
        'WH: Input → Output'                => 'المستودع: الاستلام ← الشحن',
        'WH: Stock → Customers (MTO)'       => 'المستودع: المخزون ← العملاء (MTO)',
        'WH: Input → Quality Control'       => 'المستودع: الاستلام ← مراقبة الجودة',
        'WH: Quality Control → Stock'       => 'المستودع: مراقبة الجودة ← المخزون',
        'WH: Packing Zone → Output'         => 'المستودع: منطقة التعبئة ← الشحن',
        'WH: Output → Customers'            => 'المستودع: الشحن ← العملاء',
        'WH: Input → Stock'                 => 'المستودع: الاستلام ← المخزون',
        'WH: False → Customers'             => 'المستودع: بدون مصدر ← العملاء',
        'WH: Stock (Buy)'                   => 'المستودع: المخزون (شراء)',
        'Vendors → Customers'               => 'الموردون ← العملاء',
        'WH: Stock → Pre-Production'        => 'المستودع: المخزون ← قبل الإنتاج',
        'WH: Pre-Production → Production'   => 'المستودع: قبل الإنتاج ← الإنتاج',
        'WH: Post-Production → Stock'       => 'المستودع: بعد الإنتاج ← المخزون',
    ],

    'inventories_warehouses.name' => ['Your Company' => 'المستودع الرئيسي'],

    'manufacturing_work_center_productivity_losses.name' => [
        'Material Availability' => 'توفر المواد', 'Equipment Failure' => 'عطل المعدات', 'Setup and Adjustments' => 'الإعداد والضبط',
        'Reduced Speed'         => 'انخفاض السرعة', 'Process Defect' => 'عيب في العملية', 'Reduced Yield' => 'انخفاض الإنتاجية', 'Fully Productive Time' => 'وقت منتج بالكامل',
    ],

    'payroll_salary_components.name' => [
        'Basic Salary'        => 'الراتب الأساسي', 'Housing Allowance' => 'بدل السكن', 'Transportation' => 'بدل المواصلات',
        'PASI Employee Share' => 'حصة الموظف في الحماية الاجتماعية', 'PASI Employer Share' => 'حصة صاحب العمل في الحماية الاجتماعية',
    ],

    'products_categories.name' => [
        'All'              => 'الكل', 'Consumable' => 'مستهلكات', 'Expenses' => 'مصروفات', 'Home Construction' => 'إنشاءات', 'Internal' => 'داخلي',
        'Office Furniture' => 'أثاث مكتبي', 'Outdoor furniture' => 'أثاث خارجي', 'Saleable' => 'قابل للبيع', 'Services' => 'خدمات',
    ],

    'projects_project_stages.name' => ['To Do' => 'للتنفيذ', 'In Progress' => 'قيد التنفيذ', 'Done' => 'منتهي', 'Cancelled' => 'ملغى'],

    'recruitments_applicant_categories.name' => ['Sales' => 'مبيعات', 'Manager' => 'مدير', 'IT' => 'تقنية المعلومات', 'Reserve' => 'احتياطي'],

    'recruitments_degrees.name' => ['Graduate' => 'خريج', 'Master' => 'ماجستير', 'Bachelor' => 'بكالوريوس', 'Doctoral Degree' => 'دكتوراه'],

    'recruitments_refuse_reasons.name' => [
        'Does not fit the job requirements' => 'لا يلبي متطلبات الوظيفة', 'Refused by applicant: job fit' => 'رفض المتقدم: عدم ملاءمة الوظيفة',
        'Job already fulfilled'             => 'تم شغل الوظيفة', 'Duplicate' => 'مكرر', 'Spam' => 'رسائل مزعجة', 'Refused by applicant: salary' => 'رفض المتقدم: الراتب',
    ],

    'recruitments_stages.name' => [
        'New'               => 'جديد', 'First Interview' => 'المقابلة الأولى', 'Initial Qualification' => 'التأهيل المبدئي', 'Second Interview' => 'المقابلة الثانية',
        'Contract Proposal' => 'عرض العقد', 'Contract Signed' => 'تم توقيع العقد',
    ],

    'sales_teams.name' => ['Sales' => 'المبيعات', 'Website' => 'الموقع الإلكتروني', 'Point of Sale' => 'نقطة البيع', 'Pre-Sales' => 'ما قبل البيع'],

    'time_off_leave_accrual_plans.name' => ['Seniority Plan' => 'خطة الأقدمية'],

    'time_off_leave_mandatory_days.name' => ['New Year' => 'رأس السنة الميلادية', 'Christmas' => 'عيد الميلاد'],

    'time_off_leave_types.name' => [
        'Training Time Off'      => 'إجازة تدريب', 'Paid Time Off' => 'إجازة مدفوعة', 'Parental Leaves' => 'إجازة أبوية',
        'Compensatory Days test' => 'أيام تعويضية', 'Sick Time Off' => 'إجازة مرضية', 'Unpaid' => 'إجازة بدون راتب',
    ],

    'unit_of_measures.name' => [
        'Units' => 'وحدات', 'Dozens' => 'دزينة', 'kg' => 'كجم', 'g' => 'جم', 't' => 'طن', 'lb' => 'رطل', 'oz' => 'أونصة', 'Days' => 'أيام',
        'Hours' => 'ساعات', 'm' => 'م', 'mm' => 'مم', 'km' => 'كم', 'cm' => 'سم', 'in' => 'بوصة', 'ft' => 'قدم', 'yd' => 'ياردة', 'mi' => 'ميل',
        'm²'    => 'م²', 'ft²' => 'قدم²', 'L' => 'لتر', 'm³' => 'م³', 'fl oz (US)' => 'أونصة سائلة', 'qt (US)' => 'ربع جالون', 'gal (US)' => 'جالون',
        'in³'   => 'بوصة³', 'ft³' => 'قدم³',
    ],

    'unit_of_measure_categories.name' => [
        'Unit' => 'وحدة', 'Weight' => 'الوزن', 'Working Time' => 'وقت العمل', 'Length / Distance' => 'الطول / المسافة', 'Surface' => 'المساحة', 'Volume' => 'الحجم',
    ],

    'utm_campaigns.name' => [
        'Sale' => 'تخفيضات', 'Christmas Special' => 'عرض خاص', 'Email Campaign - Services' => 'حملة بريدية - الخدمات', 'Email Campaign - Products' => 'حملة بريدية - المنتجات',
    ],

    'utm_mediums.name' => [
        'Phone'    => 'الهاتف', 'Direct' => 'مباشر', 'Email' => 'البريد الإلكتروني', 'Banner' => 'لافتة', 'X' => 'إكس', 'Facebook' => 'فيسبوك',
        'LinkedIn' => 'لينكدإن', 'Television' => 'التلفزيون', 'Google' => 'جوجل',
    ],

    'utm_sources.name' => [
        'Search engine' => 'محرك بحث', 'Lead Recall' => 'استرجاع عميل محتمل', 'Newsletter' => 'نشرة بريدية', 'Facebook' => 'فيسبوك', 'X' => 'إكس',
        'LinkedIn'      => 'لينكدإن', 'Monster' => 'مونستر', 'Glassdoor' => 'جلاس دور', 'Craigslist' => 'كريغزلست',
    ],

    'utm_stages.name' => ['New' => 'جديد', 'Schedule' => 'مجدول', 'Design' => 'تصميم', 'Sent' => 'مرسل'],

    'website_pages.title' => [
        'Home' => 'الرئيسية', 'About Us' => 'من نحن', 'Privacy Policy' => 'سياسة الخصوصية', 'Terms & Conditions' => 'الشروط والأحكام',
    ],

];
