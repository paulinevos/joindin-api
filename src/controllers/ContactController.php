<?php

/**
 * Contact us end point
 */
class ContactController extends BaseApiController
{
    public function handle(Request $request, PDO $db)
    {
        // really need to not require this to be declared
    }

    /**
     * Send an email to feedback email address
     *
     * Expected fields:
     *  - client_id
     *  - client_secret
     *  - name
     *  - email
     *  - subject
     *  - comment
     *
     * @param Request $request
     * @param PDO $db
     *
     * @throws Exception
     * @return void
     */
    public function contact(Request $request, PDO $db)
    {
        // only trusted clients can contact us to save on spam
        $clientId         = $request->getParameter('client_id');
        $clientSecret     = $request->getParameter('client_secret');
        $oauthModel = $request->getOauthModel($db);
        if (! $oauthModel->isClientPermittedPasswordGrant($clientId, $clientSecret)) {
            throw new Exception("This client cannot perform this action", 403);
        }

        $fields = ['name', 'email', 'subject', 'comment'];
        $error  = [];
        $data   = [];
        foreach ($fields as $name) {
            $value = $request->getParameter($name);
            if (empty($value)) {
                $error[] = "'$name'";
            }
            $data[$name] = $value;
        }
        if (! empty($error)) {
            $message = 'The field';
            $message .= count($error) == 1 ? ' ' : 's ';
            $message .= implode(', ', $error);
            $message .= count($error) == 1 ? ' is ' : ' are ';
            $message .= 'required.';
            throw new Exception($message, 400);
        }

        // run it by akismet if we have it
        if (isset($this->config['akismet']['apiKey'], $this->config['akismet']['blog'])) {
            $spamCheckService = new SpamCheckService(
                $this->config['akismet']['apiKey'],
                $this->config['akismet']['blog']
            );
            $isValid          = $spamCheckService->isCommentAcceptable(
                $data,
                $request->getClientIP(),
                $request->getClientUserAgent()
            );
            if (!$isValid) {
                throw new Exception("Comment failed spam check", 400);
            }
        }

        $emailService = new ContactEmailService($this->config);
        $emailService->sendEmail($data);

        $view = $request->getView();

        $view->setResponseCode(202);
        $view->setHeader('Content-Length', 0);
    }
}
