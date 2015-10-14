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
