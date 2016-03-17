<?php
class QuickbooksSyncController extends BaseController {

    public function getInfo() {
        $prefs = ORM::forTable('preferences')->findOne();
        $syncHistories = ORM::forTable('sync_histories')
            ->orderByDesc('id')
            ->limit(5)
            ->findArray();
        if ($prefs) {
            $this->renderJson([
                'qbo_consumer_key'      => $prefs->qbo_consumer_key,
                'qbo_consumer_secret'   => $prefs->qbo_consumer_secret,
                'qbo_company_id'        => $prefs->qbo_company_id,
                'last_sync_at'          => $prefs->last_sync_at,
                'qbo_token_expires_at'  => $prefs->qbo_token_expires_at,
                'sync_histories'        => $syncHistories
            ]);
        } else {
            $this->renderJson(json_decode("{}"));
        }
    }

    public function saveSetting() {
        $prefs = ORM::forTable('preferences')->findOne();
        if (!$prefs) {
            $prefs = ORM::forTable('preferences')->create();
        }
        $prefs->qbo_consumer_key = $this->data['qbo_consumer_key'];
        $prefs->qbo_consumer_secret = $this->data['qbo_consumer_secret'];
        if ($prefs->save()) {
            $this->renderJson([
                'success' => true,
                'message' => 'Updated successfully, please reconnect'
            ]);
        } else {
            $this->renderJson([
                'success' => false,
                'message' => "Failed to updated synchronize settings"
            ]);
        }
    }

    public function syncAll() {
        // $qbCreds = [
        //     'access_token' => 'qyprd4xpJ5pjHR9gvqi0Dn6f1qYWacuIAtJ4M4vSVxgAbXG4',
        //     'access_token_secret' => '1QU5O5YKb5meJVNTFB0fEKY8uzjk7lHPtZ3g3pSS',
        //     'consumer_key' => 'qyprd5907oo7C4TBBOWt279fMSudtP',
        //     'consumer_secret' => 'EO4aufQJgbfuMn7bhzXutMvDGpRA657G1iUJVXGq',
        //     'realmId' => '1006926190'
        // ];
        // $sync = new Asynchronzier($qbCreds);

        // $sync = Asynchronzier::getInstance();
        // $res = $sync->dataService->CDC(['Estimate', 'Customer'] , '2016-03-10T07:20:23-00:00');
        // dd($res);

        // $qbCustomerEntity = new IPPCustomer();
        // $qbCustomerEntity->Id = 6972;
        // dd($sync->Retrieve($qbCustomerEntity));
        if (ERPConfig::isOAuthTokenValid()) {
            $prefs = ORM::forTable('preferences')->findOne();
            try {
                $response = [];
                $startSyncAt = date('Y-m-d H:i:s');
                $syncFromTime = null;
                if ($prefs->last_sync_at) {
                    $syncFromTime = date("c", strtotime($prefs->last_sync_at));
                }

                Asynchronzier::getInstance()->start($syncFromTime);

                $prefs->last_sync_at = $startSyncAt;
                $prefs->save();
                $response = [
                    'success' => true,
                    'message' => "Sync successfully",
                    'data'    => [
                        'last_sync_at' => $startSyncAt
                    ]
                ];
            } catch(\Exception $e) {
                $response = [
                    'success' => false,
                    'message' => "An error has occurred: " . $e->getMessage()
                ];
            } finally {
                $this->renderJson($response);
            }
        } else {
            $this->renderJson([
                'success' => false,
                'message' => "OAuth tokens was not configured or expires!",
            ]);
        }
    }

    public function reconnect() {
        $currentUser = wp_get_current_user();
        if ($currentUser && in_array('administrator', $currentUser->roles)) {
            $prefs = ORM::forTable('preferences')->findOne();
            // $prefs->last_sync_at = null;
            $prefs->qbo_token_expires_at = null;
            $prefs->qbo_company_id = null;
            $prefs->qbo_oauth_token = null;
            $prefs->qbo_oauth_secret = null;
            $prefs->save();
            $this->renderJson([
                'success' => true
            ]);
        } else {
            $this->renderJson([
                'success' => false,
                'message' => "You dont have permission to perform this action!"
            ]);
        }
    }
}
?>
