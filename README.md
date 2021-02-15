# A repository service for Augmented Reality Learning Experience Models (compliant with IEEE p1589-2020)
_by Abbas Javari, Fridolin Wild (Open University)_

This plugin adds the capability to provide Augmented Reality learning experiences as activities to your courses in [Moodle](https://moodle.org/). The plugin thereby implements a service back-end to store and retrieve [IEEE p1589-2020 'ARLEM' compliant](https://standards.ieee.org/standard/1589-2020.html) activity units, which can be created and executed with compatible mobile or smart glasses apps such as [Mirage·XR](https://platform.xr4all.eu/wekit-ecs/mirage-xr/). Uploaded ARLEM units can be added as learning activities for the students to courses. We have plans to add web editing functionality, so that uploaded units can be modified or even be created directly in Moodle. Moreover, we plan to add a carousel block so that the AR learning experiences can be advertised independantly of the courses in which they feature.

This work was supported by the European Commission under the Horizon 2020 programme (H2020), as part of [ARETE](https://www.areteproject.eu/) (grant agreement no. 856533).

To activate the plugin in Moodle, you will need to:

1. Install the plugin
2. Enable Web Services from the admin panel: "Enable web services"
3. Enable REST protocol from the admin panel
4. Go to 'define roles' and enable the allow checkbox "Create a web service token" for authenticated user
5. Increase post_max_size=1024M, memory_limit=-1, and upload_max_filesize=1024M in php.ini on your server. 
