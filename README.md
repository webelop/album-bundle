#Webelop/AlbumBundle

This is a complete [Synfony](https://symfony.com/doc/current/setup.html) bundle for running a light-weight photo browsing 
and sharing website on a PHP server.

The server may resize and prepare the previews and video streams or rely on existing previews generated on the end-user's
computer. As the bundle is designed to work on low-powered devices, it is advised to install the additional helpers on
the local computers to synchronise and prepare the image previews.
 
## Installation
- First install the composer package:
```composer install webelop/album-bundle```

- Make sure you adjust the configuration in `config/packages/webelop_album.yml` or `.env`
    ```
    # Path to the pictures directory on the server
    WEBELOP_ALBUM_ROOT="/path/to/pictures"
    # Salt used to generate secure photos urls
    WEBELOP_SALT="A Not So Secret Salt. Change it!"
    ```
- Adjust the bundle url prefix in `config/routes/webelop_album.yaml`. Eg
    ```
    _webelop_album:
      resource: '@WebelopAlbumBundle/Resources/config/routes.xml'
      prefix: /album  
    ```
- Ensure security is setup for the bundle in `config/packages/security.yaml`. Eg:
  ```
      access_control:
        - { path: ^/album/manager, roles: ROLE_ADMIN }
        - { path: ^/album, roles: IS_AUTHENTICATED_ANONYMOUSLY }
  ```
- Access the site at eg: [http://localhost/album/manager](http://localhost/album/manager)

## Additional helpers:
### bin/photosync
A shell utility which uses [unison](https://www.cis.upenn.edu/~bcpierce/unison/) and helper modules to 
resize pictures, purge dropbox uploads or prepare video previews on the host computer.

When photosync is setup, computers in the household become master photo devices. Running `photosync` will 
synchronise a preset folder from any computer to the server and optionally prepare preview files. This allows the server
to be directly ready to serve the new images after successful sync.   

## Useful resources:
- [eko/docker-symfony](https://github.com/eko/docker-symfony): a complete docker-composer image for running a symfony project
- [unison](https://www.cis.upenn.edu/~bcpierce/unison/): a two-way, ssh based sync utility. It must be installed and on the same version
on both client and server.
