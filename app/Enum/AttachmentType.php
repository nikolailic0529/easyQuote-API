<?php

namespace App\Enum;

enum AttachmentType: string
{
    case PipelinerDocument = 'Pipeliner Document';
    case MaintenanceContract = 'Maintenance Contract';
    case DistributionQuotation = 'Distribution Quotation';
    case Email = 'Email';
    case ProofOfDelivery = 'Proof of delivery';
    case CustomerPurchaseOrder = 'Customer Purchase Order';
    case Image = 'Image';
}