<?php

namespace App\Domain\Pipeliner\Integration\Enum;

enum CloudObjectTypeEnum
{
    case S3FileUpload;
    case S3File;
    case S3Image;
    case GoogleDriveFile;
    case OneDriveFile;
    case BoxFile;
    case DropboxFile;
    case SharepointFile;
    case ExternalURL;
    case AlfrescoFile;
    case UrlFile;
}
