<?php

return [
    'install' => [
        'success' => 'Document Archive plugin installed successfully.',
    ],

    'navigation' => [
        'group'     => 'Document Archive',
        'documents' => [
            'label' => 'Documents',
            'icon'  => 'heroicon-o-folder-open',
        ],
        'dashboard' => [
            'label' => 'Dashboard',
            'icon'  => 'heroicon-o-squares-2x2',
        ],
        'folders' => [
            'label' => 'Folders',
            'icon'  => 'heroicon-o-folder',
        ],
        'files' => [
            'label' => 'Files',
            'icon'  => 'heroicon-o-document',
        ],
    ],

    'models' => [
        'folder' => 'Folder',
        'file'   => 'File',
    ],

    'fields' => [
        'reference_number'  => 'Reference',
        'name'              => 'Name',
        'slug'              => 'Slug',
        'description'       => 'Description',
        'parent'            => 'Parent folder',
        'color'             => 'Color',
        'icon'              => 'Icon',
        'is_private'        => 'Private',
        'password'          => 'Password',
        'sort_order'        => 'Sort order',
        'folder'            => 'Folder',
        'original_filename' => 'Original filename',
        'file'              => 'File',
        'file_size'         => 'Size',
        'mime_type'         => 'MIME type',
        'extension'         => 'Extension',
        'tags'              => 'Tags',
        'tag_name'          => 'Tag name',
        'tag_color'         => 'Tag color',
        'remove_password'   => 'Remove password',
        'expiry_date'       => 'Expiry date',
        'version'           => 'Version',
        'project'           => 'Project',
        'meeting'           => 'Meeting',
        'correspondence'    => 'Correspondence',
        'view_count'        => 'Views',
        'download_count'    => 'Downloads',
        'creator'           => 'Creator',
        'company'           => 'Company',
        'created_at'        => 'Created at',
        'updated_at'        => 'Updated at',
        'files_count'       => 'Files',
    ],

    'form' => [
        'sections' => [
            'general'   => 'General',
            'metadata'  => 'Metadata',
            'access'    => 'Access control',
            'lifecycle' => 'Lifecycle',
            'file'      => 'Document file',
        ],
        'auto_generated' => 'Auto generated',
        'file'           => [
            'create_help'         => 'Upload the document that will be stored in the archive.',
            'replace_help'        => 'The current file stays active until you upload a replacement. Previous versions are kept automatically.',
            'current'             => 'Current file',
            'replace_label'       => 'Replace with a new file',
            'replace_upload_help' => 'Optional. Leave empty to keep the existing file unchanged.',
        ],
        'access' => [
            'private_help'       => 'Private files are only visible to their creator and users with full archive access.',
            'password_status'    => 'Password protection',
            'password_enabled'   => 'Protected — preview and download require a password',
            'password_disabled'  => 'Not protected',
            'password_help'      => 'Set a password to require unlock before preview or download. The session stays unlocked for 30 minutes after a successful unlock.',
            'yes'                => 'Yes',
            'no'                 => 'No',
        ],
    ],

    'expiry' => [
        'expired_title'       => 'This document has expired',
        'expired_body'        => 'The expiry date was :date. Review or renew this document.',
        'expiring_soon_title' => 'This document expires soon',
        'expiring_soon_body'  => 'It expires on :date (:days days remaining).',
    ],

    'table' => [
        'tabs' => [
            'all'           => 'All',
            'private'       => 'Private',
            'expiring_soon' => 'Expiring soon',
            'expired'       => 'Expired',
        ],
    ],

    'actions' => [
        'preview'  => 'Preview',
        'download' => 'Download',
        'share'    => 'Share',
        'upload'   => 'Upload',
        'restore'  => 'Restore',
        'view'     => 'View details',
    ],

    'tags' => [
        'empty'             => 'No tags yet',
        'add'               => 'Add tags',
        'manage'            => 'Manage tags',
        'saved'             => 'Tags updated',
        'placeholder'       => 'Select or create tags…',
        'advanced'          => 'Advanced tag options',
        'advanced_help'     => 'Override individual tag colors manually. Most users can ignore this and use the tag picker above.',
        'custom_colors'     => 'Custom tag colors',
        'select_help'       => 'Pick existing tags or use + to create a new one with an optional color.',
        'select_help_short' => 'Search existing tags or press + to add a new one.',
    ],

    'dashboard' => [
        'page_title' => 'Document Archive Dashboard',
        'stats'      => [
            'total_files'           => 'Total files',
            'total_storage'         => 'Total storage',
            'expiring_soon'         => 'Expiring soon',
            'expiring_soon_heading' => 'Expiring within :days days',
            'expires_within_days'   => 'Expires within :days days',
            'view_all_expiring'     => 'View all expiring documents',
            'recent_uploads'        => 'Recent uploads',
        ],
        'empty' => [
            'recent_uploads' => 'No recent uploads',
            'expiring_soon'  => 'No documents expiring soon',
        ],
        'charts' => [
            'top_tags'           => 'Top tags',
            'storage_by_folder'  => 'Storage by folder',
            'files_count'        => 'Files',
            'storage'            => 'Storage (bytes)',
            'empty'              => 'No data available yet',
            'largest_folder'     => 'Largest folder: :size',
        ],
    ],

    'preview' => [
        'close'          => 'Close',
        'file_not_found' => 'File not found on disk',
        'no_preview'     => 'Preview is not available for this file type.',
        'loading'        => 'Loading preview...',
    ],

    'manager' => [
        'title'               => 'Document Manager',
        'folders'             => 'Folders',
        'search'              => 'Search files...',
        'empty'               => 'No files in this folder',
        'no_results'          => 'No files found',
        'items'               => ':count items',
        'all_files'           => 'All files',
        'root'                => 'Root',
        'filters'             => 'Filters',
        'filter_tag'          => 'Filter by tag',
        'filter_privacy'      => 'Privacy',
        'public_only'         => 'Public only',
        'reset_filters'       => 'Reset filters',
        'include_subfolders'  => 'Include subfolders',
        'view_grid'           => 'Grid view',
        'view_list'           => 'List view',
        'view_explorer'       => 'Explorer view',
        'subfolders'          => 'Subfolders',
        'details'             => 'Details',
        'select_file'         => 'Select a file to see details',
        'actions'             => [
            'label' => 'Actions',
            'more'  => 'More actions',
        ],
    ],

    'password' => [
        'title'   => 'Password required',
        'body'    => 'Enter the password to open :name',
        'submit'  => 'Unlock',
        'invalid' => 'Incorrect password.',
    ],

    'missing_file' => [
        'title' => 'File not found on disk',
        'body'  => 'The file record for :name exists, but the stored file is missing. Please re-upload the document.',
        'back'  => 'Go back',
    ],

    'permissions' => [
        'title'      => 'Folder permissions',
        'type'       => 'Type',
        'user'       => 'User',
        'role'       => 'Role',
        'permission' => 'Permission level',
        'types'      => [
            'user' => 'User',
            'role' => 'Role',
        ],
        'levels' => [
            'view'   => 'View',
            'upload' => 'Upload',
            'manage' => 'Manage',
        ],
    ],

    'validation' => [
        'invalid_extension' => 'This file type is not allowed.',
        'file_too_large'    => 'File exceeds the maximum size of :max MB.',
        'upload_missing'    => 'Uploaded file could not be found.',
    ],

    'share' => [
        'expired_title'       => 'Share link expired',
        'expired_body'        => 'This share link is no longer valid.',
        'shared_with_email'   => 'Share with email',
        'view_once'           => 'One-time view',
        'expires_at'          => 'Expires at',
        'created_title'       => 'Share link created',
        'created_body'        => 'Copy this link: :url',
        'email_subject'       => 'Shared document :reference',
        'email_heading'       => 'Document shared: :name',
        'email_body'          => 'You have been given access to document :reference.',
        'email_button'        => 'Open document',
        'email_view_once_note'=> 'This link can only be viewed once.',
        'email_expires'       => 'This link expires on :date.',
    ],

    'activity' => [
        'uploaded'      => 'Uploaded',
        'viewed'        => 'Viewed',
        'downloaded'    => 'Downloaded',
        'shared'        => 'Shared',
        'renamed'       => 'Renamed',
        'moved'         => 'Moved',
        'deleted'       => 'Deleted',
        'version_added' => 'New version added',
    ],

    'commands' => [
        'archive_expired' => [
            'done' => 'Archived :count expired documents.',
        ],
        'cleanup_share_links' => [
            'done' => 'Deactivated :count expired share links.',
        ],
    ],

    'notifications' => [
        'archived' => [
            'title' => 'Document archived',
            'body'  => 'Document :reference has been archived because it expired.',
        ],
        'uploaded' => [
            'title' => 'File uploaded',
            'body'  => 'Document :reference was uploaded successfully.',
        ],
    ],
];
