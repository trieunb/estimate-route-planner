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
