## 1.7.9
  - Bugfix: line descriptions does not break lines when print estimates
  - Auto fill phone number fields with dashs
  - Fix some issue on Ipad

## 1.7.8
  - Bugfix: Print Work order - Line descriptions does not break lines

## 1.7.7
  - Auto fill company field for sub-customer by master display name if is blank
  - Use company name instead of customer display name for print estimate

## 1.7.6
  - Crew route: change Exp date to Estimate date
  - Add `Company name` field to job request form and estimate form
  - Print estimate:
    - Decrease the font size
    - Remove service/product from description line
    - Add bullets/remove bottom borders from desciption lines

## 1.7.5
  - Work order:
    - Make the time inputs to wider.
  - Customer info pop-up:
    - Change first and last name to not required
    - Auto fills phone numbers, email, company and billing address from parent customer
    - Add button to make shipping address to same as billing address

## 1.7.4
  - Work order:
    - Save ETAs and service lines info
    - Add `Reset` button to remove saved work order info to start from scratch
    - Add `Edit` buttons next to estimate numbers
  - Add `Refresh` button for soft reloading. It's much faster than F5 or browser refresh.
  - Add link to work order to crew routes listing page

## 1.7.3
  - Improve route planning page:
    - Change color of route direction on map
    - Add color indicators and letters to item and markers.
  - Change auto fill `Display name` in format "<Last name>, <First name>"

## 1.7.2
  - Customer popup:
    - Change `First name` and `Last name` of customer to be required
    - Auto fill `Display Name` with First and Last name if blank
  - Bugfix: estimate statuses does not get synced after creating invoices.
  - Bugfix: duplicate flash messages after checking geolocation issues

## 1.7.1
  - Improve route planning page:
    - Open marker when click the item in queue list.
    - Highlight item when click on the marker.

## 1.7.1
  - Add `Display name` to customer pop-up
  - Add button to check geolocation issue for job requests and estimates list

## 1.7.0
  - Use pop-up box for create/edit customer in request and estimate forms
  - Route planning view:
    - Add compact view mode
    - Add button to quickly move items between two queues
  - Some minor UI style changes

## 1.6.5
  - Add save route function. Only basic fields, more data(estimates) will be saved on next versions.

## 1.6.4
  - Enabled create new customer function in job request form
  - Work order:
    - Replace input tag by textarea tag for service lines
  - Fixed bug: missing `Completed` status in job request form

## 1.6.3
  - Use Memcache for caching data
  - Remove create new customer from job request form
  - Remove statuses from crew routes
  - Add new `Routed` status for estimate
  - Job Request - auto change Estimator Assigned to the same with the route when
  transition from pending queue to assigned
  - Auto transition estimate status to Accepted when a signature is added
  - Change print/pdf layout for estimate

## 1.6.2
  - Fixed bug: The description field for Product/Service is limiting the amount of characters allowed.
  - Remove all required fields from Job request form except customer
  - Replace `How find us` on job request form by `Source` like estimate
  - Fixed bug unable to remove all assigned items from route
  - Change `Estimate footer` to `Accepted Agreement`
  - Add `Disclaimer` field to `Company Info`, auto pre-fill to estimate forms like estimate footer

## 1.6.1
  - Update estimate 'Source' field to sync with Class in QB
  - Change text 'Equipments' to 'Equipment list' in work order

## 1.6.0
  - Add `Secondary phone` to job request form
  - Change service lines description in worker order to editable
  - Change `Source` field in estimate form to be required
  - Remove Location notes from the PDF the gets printed or emailed to the customer
  - Add equipment list field to work order
  - Auto fill estimate date with current date
  - Auto fill expiration date with 30 days after current date
  - Add create estimate from job request
  - Fix lagging when typing on clients select box

## 1.5.7
  - Fix could not find direction in large of routes(8)
  - Some UI changes on route planning page
  - Fix description-only line does not show in worker order

## 1.5.6
  - Fix order of service lines when printing
  - Fix email not sync when create job request
  - Change `Secondary phone number` to sync as mobile phone number in QB
  - Fix escaping quote/slash characters bug
  - Use textarea tag for line description

## 1.5.5
  - Fix synchronize issue on estimate' billing/shipping addresses.
  - Fix missing description-only lines on creating work order and printing

## 1.5.4
  - Fix responsive issue for customer signature modal on mobile

## 1.5.3
  - Add auto suggestion to services dropdown(estimate form)
  - Add auto suggest customer name to search field in requests and estimates list.

## 1.5.2
  - Estimate not required product/service, qty, amount or total
  - Allow a blank service line to be between services.
  - Add sync line2, line3, .. line5 for addresses.
  - Some change to protect plugin settings from non-admin users

## 1.5.1
  - Fix sync customer country

## 1.5.0
  - Writing customer signature in a modal
  - Job request:
    - Change email to not be required
    - Add `Estimator Assigned` field.
    - Auto hide `Completed` requests form listing table
    - Add column `Estimator Assigned` to listing table.

  - Estimate route:
    - Change Recent Saved Routes to `Current Assigned Requests`.
    - Auto hide `Completed` requests from assigned list.
    - Add `Estimator Assigned` field to form.
    - Add column `Estimator Assigned` to listing table.

  - Estimate:
    - Change customer signature text to `Customer Signature Authorizes Commencement of Work`
    - Writing customer signature in a modal

  - Crew route: Change "Pending Routes" to `Accepted Estimates` and show only accepted estimates

  - Permissions:
    - `erpp_estimator_only_routes`: that only shows Estimate Routes that assigned to estimator
    - `erpp_hide_estimate_pending_list`: hides the Estimate Route pending list so the person can't assign requests to themselves.
  - Worker order: Hide product code from lines, only show Qty and Description.
## Questions:
  - Crew route does not have "Estimator Assigned" like Estimate route?

## 1.4.1
  - Decrease load time for customers list from server

## 1.4.0
  - Print country fields for estimate/ referral, worker order

## 1.3.4
  - Fix signature canvas on new estimate page sometime not drawable.

## 1.3.3
  - Fix customer signature: lagging, writing outside of box

## 1.3.2
  - Fix Drag and drop route not working on mobile browser.

## 1.3.1
  - Add mailing address to company setting. And use it for job request and estimate forms,
   instead of business address.

## 1.3.0
  - Bug fixes:
    - Empty services dropdown
    - Wrong estimate order in creating worker order
  - Change "Due" to "Exp" on all estimate related pages, such as: add/edit/list, print, route sort options.
  - Add estimators(Sold by) to worker order

## 1.2.3
  - Remove in-active customer from customer dropdowns.
  - Optimize load time for customers
  - Code re-ogranize

## 1.2.2
  - Display customer dropdown in order by alphabetic and parent and child

## 1.2.1
  - Fix lines with rate less than 0
  - Some code optimize

## 1.1.2
  - Add sub-level to customers
  - Make customers dropdown order by sub-customers
  - Remove country fields from customer(estimate) information
  - Use company address as start location for every route

## 1.1.1
  - Fix some typos on job request printing
  - Add pre-loading angular templates

## 1.1.0
  - New terms:
    - `Referral` to `Job Request`
    - `Referral Route` to `Estimate Route`
    - `Estimate Route` to `Crew Route`
  - Add searching:
    - Customer name for job requests
    - `Customer name`, `id` for estimates
    - Title for estimate and crew routes
  - Add sales rep permission:
    - Name: `erpp_view_sales_estimates`
    - Restrict to see estimates base on current user name, set by Sold By fields.
    - Disabled for administrator by default.
  - Add country to billing address and job information for job request and estimate
  - Fix some bug and typos
