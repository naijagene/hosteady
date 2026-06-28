<?php

namespace App\Modules\Sdk\Ui\Enums;

enum UiActionType: string
{
    case Navigate = 'navigate';
    case SubmitForm = 'submit_form';
    case RefreshTable = 'refresh_table';
    case OpenModal = 'open_modal';
    case DownloadReport = 'download_report';
    case StartWorkflow = 'start_workflow';
    case UploadDocument = 'upload_document';
    case SendNotification = 'send_notification';
    case Custom = 'custom';
}
