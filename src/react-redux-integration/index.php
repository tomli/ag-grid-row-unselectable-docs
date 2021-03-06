<?php
$pageTitle = "ag-Grid Detailed Guides: React Redux Integration";
$pageDescription = "ag-Grid is a feature-rich datagrid available in Free or Enterprise versions. This is a detailed guide on how to use with React and Redux. We explore how a React component using ag-Grid can take advantage of a Redux store to simplify state management.";
$pageKeyboards = "React Redux Grid";
$pageGroup = "basics";
include '../documentation-main/documentation_header.php';
?>
    <h1 id="redux-integration">
        Redux Integration
    </h1>

    <p class="lead">
       This section explores how a React component that uses the ag-Grid Data Table can take advantage of a Redux store
       to simplify state management.
    </p>

    <p>
        We will use the concrete example of a file browser component to demonstrate how to move local component state to
        a Redux store. Updates to the files state will be retrieved from the Redux store, while UI events to add, move and
        delete files will be dispatched to the Redux store for processing.
    </p>

    <p>
        <img style="margin-left: 10%" src="redux-file-browser.png" width="80%" height="4%"/>
    </p>

    <note>This section assumes the reader is familiar with React and ES6 Javascript features.</note>

    <h2 id="do-i-need-redux">Do I need Redux?</h2>

    <p>Redux excels in applications with complex state interactions. If you are passing unrelated data through several
       layers of components or data is shared and synchronized between different parts your application, you could
       benefit from Redux.</p>

    <p>When using Redux, you move state from your components into a single immutable store, where state is replaced rather
       than mutated. This combined with its unidirectional data flow can make it easier to test and reason about state
       changes in your application.</p>

    <h2 id="redux-architecture">Redux Architecture</h2>

    <p>Redux has a single store which contains all application state. It has one Root Reducer which is responsible for
       transforming the application state and is typically created by combining several smaller reducers.</p>

    <img style="margin-left: 15%" src="redux-store.png"/>

    <p>Actions describe the operation the reducer should perform on the state. They may also contain an optional payload
       containing data used by the reducer to transform the state. For example an action to create a new file might
       look like this:
    </p>

<snippet language="js">
{
    type: ‘NEW_FILE’,
    payload: {
        filePath: [‘documents’, ‘pdf’, ‘cv.pdf’]
    }
}</snippet>

    <p>Actions are dispatched to the store from UI components as illustrated below:</p>

    <img style="margin-left: 2%" src="redux-data-flow.png" width="100%" />

    <p>The reducer that is associated with the action type will then transform the state which is saved in the store.
        Reducers are pure functions which receive the current application state along with the action to be performed on
        that state to produce some new state.
    </p>

    <p style="margin-left: 20%; font-size: 20px"><b> (currentState, action) => newState </b></p>

    <p>UI components can subscribe to the store for specific parts of the state in order to be notified when there are
        changes to the store. This allows components to retrieve the latest state to refresh the view.
    </p>

    <h2 id="creating-redux-file-store">Creating our Redux File Store</h2>

    <p>
        To create our Redux File Store we will use the <code>createStore</code> factory method from the redux module.
        In our example we just require a single reducer which is supplied along with the initial state as shown below:
    </p>

<snippet language="jsx">
// store.jsx

import {createStore} from 'redux';
import fileReducer from './reducers/fileReducer.jsx';

const initialState = {
    files: [
        {id: 1, filePath: ['Documents']},
        {id: 2, filePath: ['Documents', 'txt']},
        // more files ...
    ]
};

export default createStore(fileReducer, initialState);
</snippet>

    <p>Note that in larger applications there will typically be several reducers which are combined to create a single
        root reducer. This is done using the <code>combineReducers</code> function also from Redux.</p>

    <p>Our file browser will allow users to create, move and delete files and folders. The logic for handling these
        operations will be defined in the <code>fileReducer</code> shown below:
    </p>

<snippet language="jsx">
// reducers/fileReducer.jsx

export function fileReducer(state = {}, action) {
    const payload = action.payload;
    switch (action.type) {
        case types.NEW_FILE:
            return {
                files: [
                ...state.files,
                newFile(state.files, payload.filePath)
            ]
        };
        case types.MOVE_FILES:
            return {
                files: moveFiles(state.files, payload.pathToMove, payload.targetPath)
        };
        case types.DELETE_FILES:
            return {
                files: deleteFiles(state.files, payload.pathToRemove)
            };
        default:
            return state;
    }
}
</snippet>

    <p>The <code>fileReducer</code> handles each action type with a case clause within a switch statement. It's important
        to understand that the reducer doesn't modify the state directly but instead returns a new version for the store
        to persist. As a result there must not be any side effects contained within the reducer.
    </p>

    <note>The helper methods used in the reducer are omitted for brevity but can be examined in the code tab provided
        in the example at the end of this section.
    </note>

    <p>
        Rather than create action objects directly, a more elegant approach is to create helper functions referred to as
        Action Creators as shown below:
    </p>


<snippet>
// actions/fileActions.jsx

export const actions = {
    newFile(filePath) {
        return {
            type: types.NEW_FILE,
            payload: {filePath}
        };
    },
    moveFiles(pathToMove, targetPath) {
        return {
            type: types.MOVE_FILES,
            payload: {pathToMove, targetPath}
        };
    },
    deleteFiles(pathToRemove) {
        return {
            type: types.DELETE_FILES,
            payload: {pathToRemove}
        };
    }
};
</snippet>

    <h2 id="adding-provider-component">Adding the Provider Component</h2>

    <p>Now that we have created our Redux store we need to make it available to our React <code>FileBrowser</code>
        component. This is achieved through the <code>Provider</code> component from the react-redux project.
    </p>

    <p>In the entry point of our application we wrap the <code>FileBrowser</code> component in the <code>Provider</code> component
       as shown below:
    </p>

<snippet>
// index.jsx

import React from 'react';
import {render} from 'react-dom';
import {Provider} from 'react-redux';

import store from './store.jsx';
import FileBrowser from './FileBrowser.jsx';

render(
    &lt;Provider store={store}>
        &lt;FileBrowser/>
    &lt;/Provider>,
    document.getElementById('root')
);
</snippet>

    <p>
        The <code>Provider</code> accepts the store as property and makes it available via props to all child components.
    </p>

    <h2 id="binding-react-and-redux">Binding React and Redux</h2>

    <p>In order for our File Browser to be updated when the store changes we need to bind it.</p>

    <p>This is achieved via the <code>connect</code> function provided by the react-redux project.</p>

<snippet>
import React, {Component} from 'react';
import {connect} from "react-redux";
import {bindActionCreators} from 'redux';

class FileBrowser extends Component {
    render() {

        /******* Warning, not a real implementation of render(), eg no html! */
        /******* The proper render method is given later. */

        // an immutable copy of the file state is accessed using:
        this.props.files;

        // the following actions can be dispatched to the store:
        this.props.actions.newFile(filePath);
        this.props.actions.deleteFiles(filePath);
        this.props.actions.moveFiles(movingFilePath, targetPath);
    }
}

// map files to this.props from the store state
const mapStateToProps = (state) => ({files: state.files});

// bind our actions to this.props
const mapDispatchToProps = (dispatch) => ({actions: bindActionCreators(actions, dispatch)});

// connect our component to the redux store
export default connect(mapStateToProps, mapDispatchToProps)(FileBrowser);
</snippet>
    <p>
        In the code above we pass two functions to <code>connect</code> to map the required state (mapStateToProps) and
        actions (mapDispatchToProps). When binding our actions we are using the <code>bindActionCreators</code> utility
        which wraps our action creators into a <code>dispatch</code> call.
    </p>

    <p>
        Now that our stateless <code>FileBrowser</code> component is connected to our Redux store, whenever the file
        state in the store changes, our component will re-render with the latest file state available in
        <code>this.props.files</code>.
    </p>

    <note>Bindings exist for most major javascript frameworks so Redux is not limited to React applications.</note>

    <h2 id="adding-the-data-table">Adding the Data Table</h2>

    <p>
        Now that the Redux store is now connected to our stateless React component, all that remains is to implement the
        view, which just consists of the ag-Grid Data Table in our File Browser.
    </p>

    <p>
       Before discussing the grid features in our file browser, here are all of the grid options we are using:
    </p>

<snippet language="jsx">
// FileBrowser.jsx

render() {
    return (
        &lt;div className="ag-theme-fresh">
            &lt;AgGridReact
                // provide column definitions
                columnDefs={this.colDefs}
                // specify auto group column definition
                autoGroupColumnDef={this.autoGroupColumnDef}
                // row data provided via props from the file store
                rowData={this.props.files}
                // enable tree data
                treeData={true}
                // return tree hierarchy from supplied data
                getDataPath={data => data.filePath}
                // expand tree by default
                groupDefaultExpanded={-1}
                // fit grid columns
                onGridReady={params => params.api.sizeColumnsToFit()}
                // provide context menu callback
                getContextMenuItems={this.getContextMenuItems}
                // provide row drag end callback
                onRowDragEnd={this.onRowDragEnd}
                // enable delta updates
                deltaRowDataMode={true}
                // return id required for tree data and delta updates
                getRowNodeId={data => data.id}
                // specify our FileCellRenderer component
                frameworkComponents={this.frameworkComponents}>
            &lt;/AgGridReact>
        &lt;/div>
    )
}
</snippet>

    <h2 id="tree-data">Tree Data</h2>
    <p>
        As the data is implicitly hierarchical, with a parent / child relationship between folders and files, we will use
        the grids Tree Data feature by setting <code>treeData={true}</code>.
    </p>

    <p>
        The file structure in the file browser is captured in the state as an array of files, where each array entry
        contains it's hierarchy in the <code>filePath</code> attribute.
    </p>

<snippet>
files: [
    {id: 1, filePath: ['Documents']},
    {id: 2, filePath: ['Documents', 'txt']},
    {id: 3, filePath: ['Documents', 'txt', 'notes.txt']},
    {id: 4, filePath: ['Documents', 'pdf']},
    // more files ...
]
</snippet>

    <p>
        This is supplied to the grid via the callback: <code>getDataPath={data => data.filePath}</code>.
    </p>

    <p>
        For more details see our documentation on <a href="../javascript-grid-tree-data/">Tree Data</a>.
        The mechanism for connecting Redux to ag-Grid applies equally to when the Tree Data feature is not used.
    </p>

    <h2 id="row-data-updates">Row Data Updates</h2>
    <p>
        The initial file state along with all subsequent state updates will be provided to the grid component via
        <code>rowData={this.props.files}</code> from our Redux file store.
    </p>

    <p>
        This means the grid does not change the state of the files internally but instead receives the new state from
        the Redux store!
    </p>

    <h2 id="context-menu-actions">Context Menu Actions</h2>
    <p>
        As shown above, <code>getContextMenuItems={this.getContextMenuItems}</code> supplies a function to the grid to
        retrieve the context menu items. Here is the implementation:
    </p>

<snippet>
getContextMenuItems = (params) => {
    if (!params.node) return [];
    let filePath = params.node.data ? params.node.data.filePath : [];

    let deleteItem = {
        name: "Delete",
        action: () => this.props.actions.deleteFiles(filePath)
    };

    let newItem = {
        name: "New",
        action: () => this.props.actions.newFile(filePath)
    };

    return params.node.data.file ? [deleteItem] : [newItem, deleteItem];
};
</snippet>

    <p>
        Notice that we are simply just dispatching actions to the Redux store here. For example,
        when the new file menu item is selected: <code>action: () => this.props.actions.newFile(filePath)</code>.
    </p>

    <p>
        It is important to note that nothing will happen inside the grid when a menu item is selected until the Redux
        store triggers a re-render of the grid component with the updated state.
    </p>

    <p>
        For more details see our documentation on <a href="../javascript-grid-context-menu/">Context Menu</a>.
    </p>

    <h2 id="row-drag-action">Row Drag Action</h2>
    <p>
        Just like the context menu above, we supply a callback function to handle dragging rows via:
        <code>onRowDragEnd={this.onRowDragEnd}</code>. Here is the implementation:
    <p>

<snippet>
onRowDragEnd = (event) => {
    if(event.overNode.data.file) return;

    let movingFilePath = event.node.data.filePath;
    let targetPath = event.overNode.data.filePath;

    this.props.actions.moveFiles(movingFilePath, targetPath);
};
</snippet>
    <p>
        Once again, dragging rows doesn't directly impact the state of the grid. Instead an action is dispatched to the
        Redux store using: <code>this.props.actions.moveFiles(movingFilePath, targetPath)</code>.
    </p>

    <p>
        For more details see our documentation on <a href="../javascript-grid-row-dragging/">Row Dragging</a>.
    </p>

    <h2 id="delta-updates">Delta Row Updates</h2>
    <p>
        One consequence of using Redux is that when part of the state is updated in the store, the entire state is replaced
        with a new version. Delta Row Updates is designed to work specifically with immutable stores such as Redux to ensure
        only the rows that have been updated will be re-rendered inside the grid.
    </p>
    <p>
        The file browser enables this feature using: <code>deltaRowDataMode={true}</code>, along with a required row id
        using: <code>getRowNodeId={data => data.id}</code>.
    </p>

    <p>
        This feature can lead to noticeable performance improvements in applications which contain alot of row data.
        For more details see our documentation on <a href="../javascript-grid-data-update/#delta-row-data">Delta Row Updates</a>.
    </p>

    <h2 id="custom-file-cell-renderer">Custom File Cell Renderer</h2>
    <p>
        To make our file browser more realistic we will provide a custom Cell Renderer for our files and folders. This is
        implemented as a react component as follows:
    </p>

<snippet>
// FileCellRenderer.jsx

import React, {Component} from 'react';

export default class FileCellRenderer extends Component {
    render() {
        return (
            &lt;div>
                &lt;i className={this.getFileIcon(this.props.value)}/>
                &lt;span className="filename">{this.props.value}</span>
            &lt;/div>
        );
    }
    getFileIcon = (filename) => {
        return filename.endsWith('.mp3') || filename.endsWith('.wav') ? 'fa fa-file-audio-o' :
            filename.endsWith('.xls') ? 'fa fa-file-excel-o' :
                filename.endsWith('.txt') ? 'fa fa fa-file-o' :
                    filename.endsWith('.pdf') ? 'fa fa-file-pdf-o' : 'fa fa-folder';
    }
}
</snippet>

    <p>
        The Cell Renderer is supplied to the grid through: <code>frameworkComponents={this.frameworkComponents}</code>.
        Where <code>frameworkComponents</code> is just an object referencing the imported component:
    </p>

<snippet>
import FileCellRenderer from './FileCellRenderer.jsx';

frameworkComponents = {
    fileCellRenderer: FileCellRenderer
};
</snippet>

    <p>
        The key "fileCellRenderer" is passed by name to the <code>innerRenderer</code> used in the Auto Group Column:
    </p>

<snippet>
autoGroupColumnDef = {
    headerName: "Files",
    rowDrag: true,
    sort: 'asc',
    width: 250,
    cellRendererParams: {
        suppressCount: true,
        innerRenderer: "fileCellRenderer"
    }
};
</snippet>

    <p>
        For more details see our documentation on <a href="../javascript-grid-cell-rendering-components/">Custom Cell Renderer Components</a>.
    </p>

    <h2>Demo - Redux File Browser</h2>

    <p> Now we are ready to enjoy the fruits of our labour! The completed Redux File Browser with source code is shown
        below. In this example you can:
    </p>

    <ul class="content">
        <li>Right Click on a folder for options to delete the folder or add a new file.</li>
        <li>Right Click on a file for the option to delete the file.</li>
        <li>Click and Drag on the move icon to move files and folders.</li>
    </ul>

    <?= example('Redux File Browser', 'redux-file-browser', 'react', array("enterprise" => 1, "extras" => array( "fontawesome" ))) ?>

    <h2 id="conclusion">Conclusion</h2>

    <p>
        While implementing the Redux File Browser, we have demonstrated how we can move local state out of our components,
        along with all the associated state operations, to Redux state container. This led to a nice separation of
        concerns, allowing our view components to focus on presentation detail.
    </p>

    <p>
        We also explored numerous powerful grid features that support complex grid implementations with the minimal of
        effort, while proving to be extremely flexible. These features included:
    </p>

    <ul>
        <li>Tree Data</li>
        <li>Delta Row Updates</li>
        <li>Custom Context Menu</li>
        <li>Row Dragging</li>
        <li>Delta Row Updates</li>
        <li>Custom Cell Renderer Components</li>
    </ul>

<?php include '../documentation-main/documentation_footer.php'; ?>
