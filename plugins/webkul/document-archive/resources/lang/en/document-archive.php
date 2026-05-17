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
        'tag_color'         => 'Tag color',
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
        ],
        'auto_generated' => 'Auto generated',
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
        'restore'  => 'Restore',
    ],

    'dashboard' => [
        'stats' => [
            'total_files'    => 'Total files',
            'total_storage'  => 'Total storage',
            'expiring_soon'  => 'Expiring soon',
            'recent_uploads' => 'Recent uploads',
        ],
    ],

    'manager' => [
        'title'          => 'Document Manager',
        'folders'        => 'Folders',
        'search'         => 'Search files...',
        'empty'          => 'No files in this folder',
        'no_results'     => 'No files found',
        'items'          => ':count items',
        'all_files'      => 'All files',
        'root'           => 'Root',
    ],

    'share' => [
        'expired_title' => 'Share link expired',
        'expired_body'  => 'This share link is no longer valid.',
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
    ],
];
