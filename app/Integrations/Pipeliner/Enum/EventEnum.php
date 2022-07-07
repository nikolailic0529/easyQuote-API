<?php

namespace App\Integrations\Pipeliner\Enum;

enum EventEnum
{
    case All;
    case LeadAll;
    case LeadCreate;
    case LeadUpdate;
    case LeadDelete;
    case LeadBackToLead;
    case LeadOwnerChanged;
    case LeadDocumentLinked;
    case LeadLost;
    case OpportunityAll;
    case OpportunityCreate;
    case OpportunityUpdate;
    case OpportunityDelete;
    case OpportunityMove;
    case OpportunityQualify;
    case OpportunityOwnerChanged;
    case OpportunityDocumentLinked;
    case OpportunityWon;
    case OpportunityLost;
    case ContactAll;
    case ContactCreate;
    case ContactUpdate;
    case ContactDelete;
    case ContactOwnerChanged;
    case ContactDocumentLinked;
    case AccountAll;
    case AccountCreate;
    case AccountUpdate;
    case AccountDelete;
    case AccountOwnerChanged;
    case AccountDocumentLinked;
    case TaskAll;
    case TaskCreate;
    case TaskUpdate;
    case TaskDelete;
    case TaskOwnerChanged;
    case TaskDocumentLinked;
    case TaskComment;
    case AppointmentAll;
    case AppointmentCreate;
    case AppointmentUpdate;
    case AppointmentDelete;
    case AppointmentOwnerChanged;
    case AppointmentDocumentLinked;
    case AppointmentComment;
    case MessageAll;
    case MessageCreate;
    case MessageUpdate;
    case MessageDelete;
    case MessageDocumentLinked;
    case MemoAll;
    case MemoCreate;
    case MemoUpdate;
    case MemoDelete;
    case MemoDocumentLinked;
    case MemoComment;
    case EmailAll;
    case EmailCreate;
    case EmailUpdate;
    case EmailDelete;
    case EmailDocumentLinked;
    case OpptyProductRelationAll;
    case OpptyProductRelationCreate;
    case OpptyProductRelationUpdate;
    case OpptyProductRelationDelete;
    case ProjectAll;
    case ProjectCreate;
    case ProjectUpdate;
    case ProjectDelete;
    case ProjectOwnerChanged;
    case ProjectDocumentLinked;
    case ContactAccountRelationAll;
    case ContactAccountRelationCreate;
    case ContactAccountRelationUpdate;
    case ContactAccountRelationDelete;
    case LeadOpptyAccountRelationAll;
    case LeadOpptyAccountRelationCreate;
    case LeadOpptyAccountRelationUpdate;
    case LeadOpptyAccountRelationDelete;
    case LeadOpptyContactRelationAll;
    case LeadOpptyContactRelationCreate;
    case LeadOpptyContactRelationUpdate;
    case LeadOpptyContactRelationDelete;
}