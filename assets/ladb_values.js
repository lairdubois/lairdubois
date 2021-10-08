// Publish all methods to global scope for use in templates
import ladbValues from './js/ladb/ladb-values';

global.bindNewValueProposalAjaxForm = ladbValues.bindNewValueProposalAjaxForm;
global.editValueProposal = ladbValues.editValueProposal;
global.deleteValueProposal = ladbValues.deleteValueProposal;
global.moveValueProposal = ladbValues.moveValueProposal;
global.cancelEditValueProposal = ladbValues.cancelEditValueProposal;
