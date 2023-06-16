<?php


namespace App\Common\Traits;


use App\Entity\RouteContents;
use App\Entity\Routes;
use Exception;
use Symfony\Component\HttpFoundation\Response;

trait RoutesTrait
{
    /**
     * Get all editable routes.
     * @return Routes[]
     * @throws Exception 404 : No routes found.
     */
    private function getAllRoutes(): array
    {
        $routes = $this->managerRegistry->getRepository(Routes::class)->findAll();
        if (count($routes) === 0)
        {
            throw new Exception('No route found.', Response::HTTP_NOT_FOUND);
        }
        return $routes;
    }

    /**
     * Get route by name.
     * @param string $name Route name.
     * @return Routes Route doctrine entity object.
     * @throws Exception 404 : No route found.
     */
    private function getRouteByName(string $name): Routes
    {
        $route = $this->managerRegistry->getRepository(Routes::class)->findOneBy(['name' => $name]);
        if (!$route)
        {
            throw new Exception('No route found with this name: ' . $name, Response::HTTP_NOT_FOUND);
        }
        return $route;
    }

    /**
     * Return route content by name.
     * @param string $name Route name.
     * @param string $lang Lang of route content.
     * @return RouteContents Route content doctrine entity object.
     * @throws Exception 404 : No route or route content found with this name.
     */
    private function getRouteContentByName(string $name, string $lang): RouteContents
    {
        $routeContent = null;

        try
        {
            $routeContent = $this->managerRegistry->getRepository(RouteContents::class)
                ->getRouteContentByName($name, $lang);
        }
        catch (Exception $e) { }

        if (!$routeContent)
        {
            throw new Exception('No route content found for this name: ' . $name,
                Response::HTTP_NOT_FOUND);
        }
        return $routeContent;
    }
}
