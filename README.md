# analyze

API framework adding analysis data to Drupal entities' Analyze tab.

## Analyze Module

The Analyze module provides an API framework for adding content-related 
information to Drupal entities with canonical URLs. It doesn't offer any 
standalone features but serves as a foundation for other modules to build 
on and add data to the "Analyze" tab. This module is designed to integrate 
smoothly with various content types and extend its analysis capabilities.

The main feature of the Analyze module is the "Analyze" tab that appears 
on entities. This tab provides a structured interface where other modules 
can display their analysis results.

The problem we are trying to solve is that various modules, like the 
[Realtime SEO](https://www.drupal.org/project/yoast_seo) module, the 
[Google Analytics Node Reports](https://www.drupal.org/project/ga_node_reports) 
module, and the [Editoria11y Accessibility Checker](https://www.drupal.org/project/editoria11y) 
module, all provide wildly different UI structures to content editors. The 
[Statistics](https://www.drupal.org/project/statistics) module, formerly in 
Drupal core, does not even provide a UI, except a small counter at the 
bottom of the content. You can be quite sure your content editors are not 
seeing it!

We want to unify content analysis to live inside the Analyze tab, placing 
small reports directly on the Analyze tab page and elaborate reports in 
secondary tabs. At the same time, we promote a better user experience and 
solid accessibility standards across our analytics user interfaces.

### Features
- Adds an "Analyze" tab to Drupal entities with canonical URLs.
- Provides an API for other modules to display analysis data.
- Includes a linear gauge component for displaying a value on a spectrum.

### Post-Installation
After installing the Analyze module, developers can use the provided API 
to integrate their custom analyzers. There is no immediate user-facing 
functionality or specific configuration page for the Analyze module itself. 
Configuration is typically handled through other modules that extend the 
Analyze module's functionality. Developers should ensure their modules 
correctly implement the API to display data on the "Analyze" tab.

### User Experience
As this module's main aim is to improve the user experience for content 
editors and marketers, it is opinionated about its presentation:
1. On the Analyze main page, you can display a maximum of three pieces of 
   information.
2. Currently, we support two widgets: Tables and Linear Gauges.
3. On the secondary tab pages or in your full sitewide topical report, you 
   can display anything you want, unrestricted.

The above rules will put critical content information closely at hand in 
an easily digestible and accessible format. When users are figuring out 
if and how to improve the content item, they may choose to delve deeper 
into a full report. When they choose to delve deeper into a specific 
topic, they can access a full page-specific or sitewide report where you 
have full control over the contents.

### Additional Requirements
- Only works on entities with canonical URLs.

### Recommended Modules/Libraries
The Analyze module is designed to work with other modules that provide 
specific content analysis features, such as SEO analysis tools, 
accessibility checkers, and sentiment analysis libraries.

### Community Documentation
@todo