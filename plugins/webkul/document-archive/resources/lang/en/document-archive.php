<?php

return [
    'install' => [
        'success' => 'Document Archive plugin installed successfully.',
    ],

    'navigation' => [
        'group'     => 'Document Archive',
        'documents' => [
            'label' => 'Browse Documents',
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

    'files' => [
        'page_title' => 'Document Archive Files',
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
        'title'                      => 'Document Manager',
        'folders'                    => 'Folders',
        'search'                     => 'Search files...',
        'empty'                      => 'No files in this folder',
        'empty_cta_upload'           => 'Upload a file',
        'empty_cta_folder'           => 'Create a folder',
        'no_results'                 => 'No files found',
        'items'                      => ':count items',
        'items_paginated'            => 'Showing :from–:to of :total',
        'all_files'                  => 'All files',
        'root'                       => 'Root',
        'filters'                    => 'Filters',
        'all'                        => 'All',
        'filter_tag'                 => 'Filter by tag',
        'filter_privacy'             => 'Privacy',
        'filter_from'                => 'From',
        'filter_to'                  => 'To',
        'filter_expiring'            => 'Expiring soon only',
        'public_only'                => 'Public only',
        'reset_filters'              => 'Reset filters',
        'include_subfolders'         => 'Include subfolders',
        'view_grid'                  => 'Grid view',
        'view_list'                  => 'List view',
        'view_mode_hint'             => 'Grid opens preview on click. List view is best for bulk actions and sorting.',
        'subfolders'                 => 'Subfolders',
        'details'                    => 'Details',
        'select_file'                => 'Select a file to see details',
        'open_full_form'             => 'Open full form',
        'create_folder'              => 'New folder',
        'rename_folder'              => 'Rename folder',
        'move_folder'                => 'Move folder',
        'delete_folder'              => 'Delete folder',
        'delete_folder_confirm'      => 'Are you sure you want to delete this folder?',
        'delete_folder_has_files'    => 'This folder still contains files. Move or delete them first.',
        'delete_folder_has_children' => 'This folder has subfolders. Remove them first.',
        'folder_created'             => 'Folder created',
        'folder_renamed'             => 'Folder renamed',
        'folder_moved'               => 'Folder moved',
        'folder_deleted'             => 'Folder deleted',
        'folder_permissions'         => 'Permissions',
        'toggle_folders'             => 'Browse folders',
        'close_folders'              => 'Close folders',
        'expand_folder'              => 'Expand folder',
        'collapse_folder'            => 'Collapse folder',
        'select_all'                 => 'Select all on page',
        'clear_selection'            => 'Clear selection',
        'selected_count'             => ':count selected',
        'back_to_manager'            => 'Back to Document Manager',
        'advanced_list_banner'       => 'For daily browsing and uploads, use the Document Manager.',
        'open_manager'               => 'Open Document Manager',
        'actions'                    => [
            'label' => 'Actions',
            'more'  => 'More actions',
        ],
        'bulk' => [
            'move'              => 'Move to folder',
            'tags'              => 'Apply tags',
            'delete'            => 'Delete selected',
            'delete_confirm'    => 'Delete :count selected file(s)? This cannot be undone.',
            'download'          => 'Download',
            'download_denied'   => 'You do not have permission to download the selected files.',
            'moved'             => 'Files moved',
            'moved_body'        => ':count file(s) moved successfully.',
            'tags_applied'      => 'Tags applied',
            'tags_applied_body' => ':count file(s) updated.',
            'deleted'           => 'Files deleted',
            'deleted_body'      => ':count file(s) deleted.',
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
