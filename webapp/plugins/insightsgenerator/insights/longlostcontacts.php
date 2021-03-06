<?php
/*
 Plugin Name: Long-lost Contacts
 Description: People you follow and haven't replied to in over a year.
 When: Thursdays for Twitter, Mondays otherwise
 */

/**
 *
 * ThinkUp/webapp/plugins/insightsgenerator/insights/longlostcontacts.php
 *
 * Copyright (c) 2012-2016 Nilaksh Das, Gina Trapani
 *
 * LICENSE:
 *
 * This file is part of ThinkUp (http://thinkup.com).
 *
 * ThinkUp is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, either version 2 of the License, or (at your option) any
 * later version.
 *
 * ThinkUp is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with ThinkUp.  If not, see
 * <http://www.gnu.org/licenses/>.
 *
 * @license http://www.gnu.org/licenses/gpl.html
 * @copyright 2012-2016 Nilaksh Das, Gina Trapani
 * @author Nilaksh Das <nilakshdas [at] gmail [dot] com>
 */

class LongLostContactsInsight extends InsightPluginParent implements InsightPlugin {
    public function generateInsight(Instance $instance, User $user, $last_week_of_posts, $number_days) {
        parent::generateInsight($instance, $user, $last_week_of_posts, $number_days);
        $this->logger->logInfo("Begin generating insight", __METHOD__.','.__LINE__);

        if ($instance->network == 'twitter') {
            $day_of_week = 4;
        } else {
            $day_of_week = 1;
        }
        $should_generate_insight = self::shouldGenerateWeeklyInsight('long_lost_contacts', $instance,
            $insight_date='today', $regenerate_existing_insight=false, $day_of_week = $day_of_week);

        if ($should_generate_insight) {
            $follow_dao = DAOFactory::getDAO('FollowDAO');

            $contacts = $follow_dao->getFolloweesRepliedToThisWeekLastYear(
            $instance->network_user_id, $instance->network);
            $long_lost_contacts = array();
            $insight_text = '';

            if (count($contacts)) {
                $post_dao = DAOFactory::getDAO('PostDAO');

                foreach ($contacts as $contact) {
                    if ($post_dao->getDaysAgoSinceUserRepliedToRecipient(
                    $instance->network_user_id, $contact->user_id, $instance->network) >= 365) {
                        $long_lost_contacts[] = $contact;
                    }
                }
            }

            if (count($long_lost_contacts)) {
                $headline = $this->username." hasn't replied to "
                    .((count($long_lost_contacts) > 1) ?
                    "<strong>".count($long_lost_contacts)." people</strong> " : $long_lost_contacts[0]->full_name." ")
                    ."in over a year";

                $insight_text = "It's good to reflect after some time has passed.";

                //Instantiate the Insight object
                $my_insight = new Insight();

                //REQUIRED: Set the insight's required attributes
                $my_insight->slug = 'long_lost_contacts'; //slug to label this insight's content
                $my_insight->instance_id = $instance->id;
                $my_insight->date = $this->insight_date; //date is often this or $simplified_post_date
                $my_insight->headline = $headline; // or just set a string like 'Ohai';
                $my_insight->text = $insight_text; // or just set a strong like "Greetings humans";
                if (count($long_lost_contacts) == 1) {
                    $my_insight->header_image = $long_lost_contacts[0]->avatar;
                }
                $my_insight->filename = basename(__FILE__, ".php");
                $my_insight->emphasis = Insight::EMPHASIS_MED;
                $my_insight->setPeople($long_lost_contacts);

                $this->insight_dao->insertInsight($my_insight);
            }
        }

        $this->logger->logInfo("Done generating insight", __METHOD__.','.__LINE__);
    }
}

$insights_plugin_registrar = PluginRegistrarInsights::getInstance();
$insights_plugin_registrar->registerInsightPlugin('LongLostContactsInsight');
