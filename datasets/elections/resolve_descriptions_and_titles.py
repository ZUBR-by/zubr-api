#!/usr/bin/python

from enum import Enum
from difflib import SequenceMatcher
import glob
import csv
import sys

class OrganizationFields(Enum):
    ID = 'id'
    NAME = 'name'

class MemberFields(Enum):
    ID = 'id'
    FULL_NAME = 'full_name'
    DESCRIPTION = 'description'
    POSITION_TYPE = 'position_type'
    REFERRAL_ID = 'referral_id'
    EMPLOYER_ID = 'employer_id'
    PHOTO_URL = 'photo_url'
    PHOTO_ORIGIN = 'photo_origin'
    NOTES = 'notes'
    WORK_TITLE = 'work_title'
    LINKS = 'links'
    PHOTO_URL_2 = 'photo_url_2'
    PHOTO_ORIGIN_2 = 'photo_origin_2'
    NOTES_2 = 'notes_2'
    WORK_TITLE_2 = 'work_title_2'
    LINKS_2 = 'links_2'
    COMMISSION_ID = 'commission_id'


def extract_organization(field_value, organizations_dict):
    org_names = organizations_dict.keys()
    field_tokens = field_value.split(' ')
    
    global_likelihood = 0
    most_likely_org_name = ''
    matching_organization = None
    for org_name in org_names:

        most_matching_org_name = ''
        max_likelihood = 0
        for token_idx in range(len(field_tokens)):
            potential_org_name = ' '.join(field_tokens[- (token_idx + 1):])
            likelihood = SequenceMatcher(None, potential_org_name, org_name).ratio() * ((min(len(potential_org_name), len(org_name)) / max(len(potential_org_name), len(org_name))) ** 4)
            if likelihood >= max_likelihood:
                max_likelihood = likelihood
                most_matching_org_name = potential_org_name
            else:
                break
            
        if max_likelihood > global_likelihood:
            global_likelihood = max_likelihood
            most_likely_org_name = most_matching_org_name
            matching_organization = organizations_dict[org_name]

        if global_likelihood == 1:
            break
            
    return (most_likely_org_name, global_likelihood, matching_organization)


def update_field(member, field, organizations_dict, org_id_mapping_field, file, skip_interactive):
    field_value = member[field]
    if field_value:

        (org_name, likelihood, organization) = extract_organization(field_value, organizations_dict)
        print('======> Resolving organization in {}, likelihood: {}'.format(field, likelihood))
        print(' '*7, "'{}'".format(field_value))
        original_org_id = organization[OrganizationFields.ID.value]
        original_org_name = organization[OrganizationFields.NAME.value]

        if likelihood == 1 or member[org_id_mapping_field] == organization[OrganizationFields.ID.value]:
            print(' '*7, 'Replacing organization:', org_name)
            member[field] = field_value.replace(org_name, '').strip()
            print('')
            return

        elif likelihood > 0.7:
            if skip_interactive:
                print(' '*7, 'Postponing for manual intervaention')
                if member not in INTERACTIVE_UPDATE_REQUIRED:
                    INTERACTIVE_UPDATE_REQUIRED.append(member)
                print('')
                return
            else:
                print(' '*7, "Looks like\t'{}'".format(org_name))
                print(' '*7, "is org {}:\t'{}'".format(original_org_id, original_org_name))
                print(' '*7, "but {} is not set".format(org_id_mapping_field))
                res = input('        Is that correct? (y)/n:')
                while res and res != 'y' and res != 'n':
                    res = input('        Is that correct? (y)/n:')

                if not res or res == 'y':
                    print(' '*7, 'Replacing organization:', org_name)
                    member[field] = field_value.replace(org_name, '').strip()
                    print(' '*7, "Setting {} to {}".format(org_id_mapping_field, original_org_id))
                    member[org_id_mapping_field] = original_org_id
                    print('')
                    return

        if likelihood > 0.55:
            if skip_interactive:
                print(' '*7, 'Postponing for manual intervaention')
                if member not in INTERACTIVE_UPDATE_REQUIRED:
                    INTERACTIVE_UPDATE_REQUIRED.append(member)
                print('')
                return
            else:
                print(' '*7, "'{}' seems to be an organization, but no mapping found".format(org_name))
                print(' '*7, "The closest org is {}: '{}'".format(original_org_id, original_org_name))
                res = input('        Still replace? y/(n):')
                while res and res != 'y' and res != 'n':
                    res = input('       Still replace? y/(n):')

                if res == 'y':
                    print(' '*7, 'Replacing organization:', org_name)
                    member[field] = field_value.replace(org_name, '').strip()
                    UNRESOLVED_ORGS.append((member, file, org_name, field))

        print('')

INTERACTIVE_UPDATE_REQUIRED = []
UNRESOLVED_ORGS = []

with open('trash/organization-all.csv', newline='', encoding='utf-8-sig') as organization_input_csv:

    organizations = list(csv.DictReader(organization_input_csv))
    organizations = list(filter(lambda o: o[OrganizationFields.NAME.value], organizations))
    organizations_by_name = dict(zip(map(lambda o: o[OrganizationFields.NAME.value], organizations), organizations))

file_name = sys.argv[1] 
with open(file_name, newline='', encoding='utf-8-sig') as input_csv:
    print('moving into', file_name)
    members = list(csv.DictReader(input_csv))
    if members:
        with open(file_name.replace('.csv', '-merged.csv'), 'w', newline='', encoding='utf-8-sig') as output_csv:
            writer = csv.DictWriter(output_csv, fieldnames=list(map(lambda f: f.value, MemberFields)))
            writer.writeheader()
            
            for member in members:
                print('==> Auto Resolving', member[MemberFields.ID.value], member[MemberFields.FULL_NAME.value])

                update_field(member, MemberFields.DESCRIPTION.value, organizations_by_name, MemberFields.REFERRAL_ID.value, file_name, True)
                update_field(member, MemberFields.WORK_TITLE.value, organizations_by_name, MemberFields.EMPLOYER_ID.value, file_name, True)

            for member in INTERACTIVE_UPDATE_REQUIRED:
                print('==> Manual Resolving', member[MemberFields.ID.value], member[MemberFields.FULL_NAME.value])

                update_field(member, MemberFields.DESCRIPTION.value, organizations_by_name, MemberFields.REFERRAL_ID.value, file_name, False)
                update_field(member, MemberFields.WORK_TITLE.value, organizations_by_name, MemberFields.EMPLOYER_ID.value, file_name, False)

            for member in members:
                writer.writerow(member)

for unresovled in UNRESOLVED_ORGS:
    print("UNRESOLVED org '{}' found in {} field for member {}: {} in file {}".format(unresolved[2], unresolved[3], unresolved[0][MemberFields.ID.value], unresolved[0][MemberFields.FULL_NAME.value], unresolved[1]))
