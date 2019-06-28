<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function install(Request $request)
    {
        if(!$request->method('get')) {
            return response()->json(['error' => 'Method not allowed'], 405);
        }

        $code = $request->query('code');
        $installId = $request->query('installation_id');

        try {
            $accessToken = $this->getAccessToken($code);
            $userData = $this->getUserInformation($accessToken);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], ($e->getCode() ? $e->getCode() : '400'));
        }

        $user = new User;

        $user->username = $userData->login;
        $user->full_name = $userData->name;
        $user->email = $userData->email;
        $user->github_id = $userData->id;
        $user->installation_id = $installId;
        $user->access_token = $accessToken;
        $user->url = $userData->url;
        $user->avatar_url = $userData->avatar_url;

        $user->save();

        return response()->json($user, 200);

    }


    /**
     * Get the access token from Github by exchanging the code from the request
     * @param  string $code The code received from Github in the request
     * @return string       The access code for the user
     */
    protected function getAccessToken(string $code) {
        $post = [
            'client_id'     => env('GITHUB_APP_ID'),
            'client_secret' => env('GITHUB_APP_SECRET'),
            'code'          => $code,
        ];

        $ch = curl_init('https://github.com/login/oauth/access_token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);

        $response = curl_exec($ch);

        curl_close($ch);

        if ($response === false || $response == '') {
            throw new \Exception("No response from Github... Please try again", 417);
        }

        $params = array();
        \parse_str($response, $params);

        if (isset($params['error'])) {
            throw new \Exception($params['error'] . ': ' . $params['error_description'], 400);
        }

        if (!$params['access_token']) {
            throw new \Exception('No Access Token in response', 400);
        }

        return $params['access_token'];
    }



    protected function getUserInformation(string $accessToken)
    {

        $header = array(
            'Accept: application/json',
            'Authorization: Bearer ' . $accessToken
        );

        $options = array(
            CURLOPT_USERAGENT => "Theme Package Manager by Bram Korsten",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $header,
        );

        $ch = curl_init('https://api.github.com/user');
        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);

        curl_close($ch);

        if ($response === false || $response == '') {
            throw new \Exception("No response from Github while fetching userdata... Please try again", 417);
        }

        $response = \json_decode($response);

        return $response;

    }
}
