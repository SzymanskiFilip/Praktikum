<?php


namespace App\Service;


use Symfony\Component\HttpFoundation\Request;

/**
 * Class QueryService
 *
 * Works with the query string and params of a given request.
 *
 * @package App\Service
 */
class QueryService
{
    /**
     * Extract the query params from the request.
     *
     * @param Request $request
     * @return array
     */
    public function getQueryParameter(Request $request): array
    {
        // set empty array as default return value
        $queryParams = [];

        /*
         * Extrahiere aus dem gegebenen Request den QueryString und erstelle aus diesem ein Array.
         * Die Werte sollen als Key den Key haben, welcher auch im QueryString verwendet wurden.
         *
         * Verwende das Array 'queryParams'
         */

        $queryString = $request->getQueryString();
        $params = explode("&", $queryString);

        for($x = 0; $x < count($params); $x++){
            $keyAndValue = explode("=", $params[$x]);
            $queryParams[$keyAndValue[0]] = $keyAndValue[1];
        }


        // return the parsed query params
        return $queryParams;
    }
}