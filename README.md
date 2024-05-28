<h2>Welcome to Nestor</h2>

Nestor is a web application framework for creating content management scenarios, that I designed and built while working at Cultivate Learning, a University of Washington grant funded center supporting and improving the quality of early childhood education.

Nestor uses a component based architecture in which archetypes of web functionality, represented by classes, that can be hierarchally arranged to describe the desired functionality of a content area of a web application. The component architecture allows for a "services neutral" solution, avoiding the pitfalls of creating web applications that often become tied to specific services and hosting solutions. The component based architecture also allows for a manageable unified codebase while providing the flexibility required where a multi-tenant solution would not be adequate due to the high level of customization required to meet the needs to the various communities that these web applications serve.

Nestor uses a key + value storage approach within a MySQL database, reflecting a loosely typed strategy for storing component metadata.

<h3>Notes</h3>

Nestor is written in PHP, uses a MySQL database, and will run on standard LAMP stack.

The installer is very utilitarian and was built by a member of my team. It works, but could stand to be rewritten.

The core web application framework found here has been release as open source under the MIT License. The Nestor components code used to create specific web applications used at Cultivate Learning however have not been released as open source, reflecting an agreement with leadership.

Documentation can be found at <a href="https://nestor-cms.github.io/documentation/index.htm">https://nestor-cms.github.io/documentation/index.htm</a>, but is however extremely out of date.
