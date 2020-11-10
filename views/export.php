<xml>
    <users>
        <?php foreach ($user_arr as $user): ?>
            <user>
                <record_type>PUBLIC</record_type>
                <primary_id><?php echo $user->alma_match_id; ?></primary_id>
                <external_id>SIS</external_id>
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
                    <addresses>
                    <?php if (!is_null($user->address)): ?>
                        <address preferred="true">
                            <line1><?php echo xml_convert($user->address->strasse); ?></line1>
                            <postal_code><?php echo $user->address->plz; ?></postal_code>
                            <city><?php echo $user->address->ort; ?></city>
                            <country><?php echo $user->address->nation; ?></country>
                            <address_types>
                                <address_type desc="<?php echo $user->address_type_desc; ?>"><?php echo $user->address_type; ?></address_type>
                            </address_types>
                        </address>
                    <?php endif; ?>
                    </addresses>
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
                    <phones>
                    <?php if (!is_null($user->phone_number)): ?>
                        <phone preferred="true">
                            <phone_number><?php echo $user->phone_number; ?></phone_number>
                            <phone_types>
                                <phone_type><?php echo $user->phone_type_desc; ?></phone_type>
                            </phone_types>
                        </phone>
                    <?php endif; ?>
                    </phones>
                </contact_info>
                <user_identifiers>
                    <user_identifier>
                        <id_type desc="Match_ID">01</id_type>
                        <value><?php echo $user->alma_match_id; ?></value>
                    </user_identifier>
                    <user_identifier>
                        <id_type desc="Campus_Card_ID">02</id_type>
                        <value><?php echo $user->campus_card_id; ?></value>
                    </user_identifier>
                    <user_identifier>
                        <id_type desc="UID">05</id_type>
                        <value><?php echo $user->uid; ?></value>
                    </user_identifier>
                </user_identifiers>
            </user>
        <?php endforeach; ?>
    </users>
</xml>
