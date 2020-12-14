<?php
$user_identifier_match_id = $this->config->item('user_identifier_match_id')
	?: show_error('Missing config entry for user_identifier_match_id');
$user_identifier_campuscard_id = $this->config->item('user_identifier_campuscard_id')
	?: show_error('Missing config entry for user_identifier_campuscard_id');
$user_identifier_uid = $this->config->item('user_identifier_uid')
	?: show_error('Missing config entry for user_identifier_uid');
?>

<users>
    <?php foreach ($user_arr as $user): ?>
        <user>
            <record_type>PUBLIC</record_type>
            <primary_id><?php echo $user->alma_match_id; ?></primary_id>
            <external_id>SIS</external_id>
            <preferred_language>de</preferred_language>
            <first_name><?php echo $user->first_name; ?></first_name>
            <last_name><?php echo $user->last_name; ?></last_name>
            <user_title><?php echo $user->user_title; ?></user_title>
            <gender><?php echo $user->gender; ?></gender>
            <account_type>EXTERNAL</account_type>
            <status>ACTIVE</status>
            <birth_date><?php echo $user->birth_date; ?></birth_date>
            <expiry_date><?php echo $user->expiry_date; ?></expiry_date>
            <?php if (!is_null($user->purge_date)): ?>
            <purge_date><?php echo $user->purge_date; ?></purge_date>
            <?php endif; ?>
            <user_group desc="<?php echo $user->user_group_desc; ?>"><?php echo $user->user_group; ?></user_group>
            <contact_info>
                <emails>
                <?php if (!is_null($user->email_address)): ?>
                    <email preferred="true">
                        <email_address><?php echo $user->email_address; ?></email_address>
                        <email_types>
                            <email_type desc="<?php echo $user->email_type_desc; ?>"><?php echo $user->email_type_desc; ?></email_type>
                        </email_types>
                    </email>
                <?php endif; ?>
                </emails>
            </contact_info>
            <user_identifiers>
                <user_identifier>
                    <id_type desc="Match_ID"><?php echo $user_identifier_match_id; ?></id_type>
                    <value><?php echo $user->alma_match_id; ?></value>
                </user_identifier>
                <user_identifier>
                    <id_type desc="Campus_Card_ID"><?php echo $user_identifier_campuscard_id; ?></id_type>
                    <value><?php echo $user->campus_card_id; ?></value>
                </user_identifier>
                <user_identifier>
                    <id_type desc="UID"><?php echo $user_identifier_uid; ?></id_type>
                    <value><?php echo $user->uid; ?></value>
                </user_identifier>
            </user_identifiers>
        </user>
    <?php endforeach; ?>
</users>

