Jitsi Meet module
=================

![screenshot](https://gitlab.com/francoisjacquet/Jitsi_Meet/raw/master/screenshot.png?inline=false)

https://www.rosariosis.org/modules/jitsi-meet/

Version 1.2 - May, 2020

License GNU GPL v2

Author François Jacquet

Sponsored by Santa Cecilia school, Salvador

DESCRIPTION
-----------
This RosarioSIS module uses [Jitsi Meet](https://jitsi.org/jitsi-meet/) to allow students and users (admin, teachers, or parents) to participate into virtual conference rooms with video and audio capabilities.

Features:
- A room where all participants can meet each other.
- Automatic customization of the room's subject and the name/avatar of the participants.
- Customize all the parameters supported by the [Jitsi Meet API](https://github.com/jitsi/jitsi-meet/blob/master/doc/api.md).

The module uses by default the free meet.jit.si service which is maintained by the Jitsi team at 8x8. However, if you want to use your own hosted installation of Jitsi Meet, you can configure the corresponding domain via the "Configuration" program.

More information about Jitsi Meet:
- [FAQ](https://jitsi.org/user-faq)
- [Jitsi Community Forum](https://community.jitsi.org/)

Translated in [French](https://www.rosariosis.org/fr/modules/jitsi-meet/) and [Spanish](https://www.rosariosis.org/es/modules/jitsi-meet/).

### Common Issues

#### Jitsi Meet cannot access my microphone or camera

Jitsi Meet uses your browser's API to ask for permissions to access your microphone or camera. In case you get an error that your device can not by accessed or used, please check one of the following:

- Another application uses the device.
- Your browsing context is insecure (that is, the page was loaded using HTTP rather than HTTPS).
- You denied access to your browser when you were asked for.
- You have denied globally access to all applications via your browser's configuration.

#### I have a gray box

You must enable / accept third-party cookies. Please check your browser settings.

#### Branded watermark is not displayed

Please note that this setting can only be used if you have set up your own Jitsi Meet server installation.


CONTENT
-------

Jitsi Meet
- Meet
- My Rooms
- Configuration

INSTALL
-------
Copy the `Jitsi_Meet/` folder (if named `Jitsi_Meet-master`, rename it) and its content inside the `modules/` folder of RosarioSIS.

Go to _School > Configuration > Modules_ and click "Activate".

Requires RosarioSIS 5.0+
